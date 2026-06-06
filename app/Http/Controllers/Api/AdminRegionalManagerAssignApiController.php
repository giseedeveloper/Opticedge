<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\User;
use App\Services\DeviceHierarchyAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminRegionalManagerAssignApiController extends Controller
{
    public function formData(): JsonResponse
    {
        $managers = User::query()
            ->where('role', 'regional_manager')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $purchases = Purchase::stockPurchases()
            ->with(['product.category', 'lines.product.category'])
            ->where(function ($q) {
                $q->whereNotNull('product_id')->orWhereHas('lines');
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Purchase $p) => [
                'id' => $p->id,
                'label' => trim(($p->name ?? 'Purchase #'.$p->id).' · '.($p->date?->format('M j, Y') ?? '')),
                'date' => $p->date?->toDateString(),
            ])
            ->values();

        return response()->json([
            'data' => [
                'regional_managers' => $managers,
                'purchases' => $purchases,
            ],
        ]);
    }

    public function assignableModels(Purchase $purchase): JsonResponse
    {
        if ($purchase->isPassthrough()) {
            return response()->json(['data' => []]);
        }

        $purchase->load(['lines.product.category', 'product.category']);
        $purchaseId = (int) $purchase->id;

        // Use already-loaded product relations to avoid a secondary whereIn query.
        $products = collect();
        if ($purchase->product) {
            $products->put((int) $purchase->product->id, $purchase->product);
        }
        foreach ($purchase->lines as $line) {
            if ($line->product && ! $products->has((int) $line->product->id)) {
                $products->put((int) $line->product->id, $line->product);
            }
        }

        $registeredProductIds = ProductListItem::onPurchaseStock($purchaseId)
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id);

        foreach ($registeredProductIds as $rpid) {
            if (! $products->has($rpid)) {
                $extra = Product::with('category')->find($rpid);
                if ($extra) {
                    $products->put($rpid, $extra);
                }
            }
        }

        if ($products->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $data = $products->map(function ($product) use ($purchaseId) {
            $pid = (int) $product->id;

            $available = ProductListItem::assignableFromAdminOnPurchase($purchaseId, $pid)->count();
            $totalRegistered = ProductListItem::onPurchaseStock($purchaseId)->matchingCatalogProduct($pid)->count();
            $inDistribution = Schema::hasColumn('product_list', 'distribution_sale_id')
                ? ProductListItem::onPurchaseStock($purchaseId)
                    ->matchingCatalogProduct($pid)
                    ->whereNotNull('distribution_sale_id')
                    ->count()
                : 0;
            $categoryName = $product->category?->name ?? '—';

            return [
                'product_id' => $pid,
                'label' => $categoryName.' — '.$product->name,
                'available_imeis' => $available,
                'in_distribution' => $inDistribution,
                'total_registered' => $totalRegistered,
                'selectable' => true,
                'assignable' => $available > 0,
            ];
        })
            ->sortBy('label')
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }

    public function assignableImeis(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
            'purchase_id' => 'required|exists:purchases,id',
        ]);

        $productId = (int) $validated['product_id'];
        $purchaseId = (int) $validated['purchase_id'];

        $items = ProductListItem::onPurchaseStock($purchaseId)
            ->matchingCatalogProduct($productId)
            ->with([
                'distributionSale:id,dealer_name,date,status',
                'regionalManagerProductListAssignment.regionalManager:id,name',
                'teamLeaderProductListAssignment.teamLeader:id,name',
                'agentProductListAssignment.agent:id,name',
            ])
            ->orderBy('imei_number')
            ->get();

        $rows = $items->map(function (ProductListItem $item) {
            $status = $item->custodyStatusForAdminAssign();

            return [
                'id' => $item->id,
                'imei_number' => $item->imei_number,
                'model' => $item->model,
                'text' => $item->imei_number.($item->model ? ' – '.$item->model : ''),
                'selectable' => $status['selectable'],
                'status' => $status['code'],
                'status_label' => $status['label'],
            ];
        })->values();

        return response()->json([
            'data' => $rows->all(),
            'summary' => [
                'total' => $rows->count(),
                'available' => $rows->where('selectable', true)->count(),
                'in_distribution' => $rows->where('status', 'distribution')->count(),
                'other' => $rows->where('selectable', false)->where('status', '!=', 'distribution')->count(),
            ],
        ]);
    }

    public function store(Request $request, DeviceHierarchyAssignmentService $hierarchyService): JsonResponse
    {
        $validated = $request->validate([
            'regional_manager_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'regional_manager')),
            ],
            'purchase_id' => 'required|exists:purchases,id',
            'product_id' => 'required|exists:models,id',
            'product_list_ids' => 'required|array|min:1',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        $regionalManager = User::findOrFail($validated['regional_manager_id']);
        $purchaseId = (int) $validated['purchase_id'];
        $productId = (int) $validated['product_id'];
        $imeiIds = array_values(array_unique(array_map('intval', $validated['product_list_ids'])));

        $eligibleIds = ProductListItem::assignableFromAdminOnPurchase($purchaseId, $productId)
            ->whereIn('id', $imeiIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($eligibleIds) !== count($imeiIds)) {
            return response()->json(['message' => 'One or more selected IMEIs are not available on this purchase.'], 422);
        }

        try {
            $count = $hierarchyService->assignToRegionalManager(
                $regionalManager,
                $productId,
                $imeiIds
            );
            $message = $count === 1
                ? '1 device assigned to regional manager.'
                : "{$count} devices assigned to regional manager.";
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => $message, 'count' => $count]);
    }
}
