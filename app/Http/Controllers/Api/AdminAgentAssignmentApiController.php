<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\User;
use App\Services\DeviceHierarchyAssignmentService;
use Illuminate\Http\Request;

class AdminAgentAssignmentApiController extends Controller
{
    public function __construct(
        private DeviceHierarchyAssignmentService $hierarchyService
    ) {}

    /**
     * Products that have at least one purchase (same rule as web assign form).
     */
    public function productsForAssign()
    {
        $products = Product::query()
            ->whereHas('purchases')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id']);

        $purchases = Purchase::query()
            ->with('product.category')
            ->whereNotNull('product_id')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category_id' => $p->category_id,
            ])->values()->all(),
            'purchases' => $purchases->map(function (Purchase $purchase) {
                return [
                    'id' => $purchase->id,
                    'name' => $purchase->name ?: ('Purchase #' . $purchase->id),
                    'product_id' => $purchase->product_id,
                    'model' => $purchase->product?->name,
                    'category_name' => $purchase->product?->category?->name,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * Unsold IMEIs in admin warehouse for this catalog product.
     */
    public function assignableImeis(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:models,id',
        ]);

        $items = ProductListItem::assignableFromAdmin((int) $validated['product_id'])
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json([
            'data' => $items->map(fn ($i) => [
                'id' => $i->id,
                'imei_number' => $i->imei_number,
                'model' => $i->model,
                'text' => $i->imei_number . ($i->model ? ' – ' . $i->model : ''),
            ])->values()->all(),
        ]);
    }

    /**
     * Resolve scanned / typed text to one assignable product_list row for the product.
     */
    public function validateImei(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:models,id',
            'imei' => 'required|string|max:512',
        ]);

        $productId = (int) $validated['product_id'];
        $raw = trim($validated['imei']);
        $normalized = preg_replace('/\s+/u', '', $raw) ?? $raw;

        $items = ProductListItem::assignableFromAdmin($productId)
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        $match = null;
        foreach ($items as $item) {
            $im = trim((string) $item->imei_number);
            if ($im === '') {
                continue;
            }
            $imNorm = preg_replace('/\s+/u', '', $im) ?? $im;
            if (strcasecmp($im, $raw) === 0 || strcasecmp($imNorm, $normalized) === 0) {
                $match = $item;
                break;
            }
        }

        if ($match === null) {
            foreach ($items as $item) {
                $im = trim((string) $item->imei_number);
                if ($im === '') {
                    continue;
                }
                if (stripos($raw, $im) !== false || stripos($normalized, preg_replace('/\s+/u', '', $im) ?? $im) !== false) {
                    $match = $item;
                    break;
                }
            }
        }

        if ($match === null) {
            return response()->json([
                'valid' => false,
                'message' => 'No assignable device matches this scan for the selected product.',
                'data' => null,
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => null,
            'data' => [
                'product_list_id' => $match->id,
                'imei_number' => $match->imei_number,
                'model' => $match->model,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'regional_manager_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:models,id',
            'product_list_ids' => 'required|array|min:1',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        $user = User::findOrFail($validated['regional_manager_id']);

        try {
            $added = $this->hierarchyService->assignToRegionalManager(
                $user,
                (int) $validated['product_id'],
                $validated['product_list_ids']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Devices assigned to regional manager.',
            'data' => [
                'assigned_count' => $added,
            ],
        ], 201);
    }
}
