<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegionalManagerDeviceReturn;
use App\Services\RegionalManagerDeviceReturnService;
use Illuminate\Http\Request;

class AdminDeviceReturnApiController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = RegionalManagerDeviceReturn::query()
            ->with(['fromRegionalManager', 'items', 'decidedByUser'])
            ->latest();

        if ($status && in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $q->where('status', $status);
        }

        $page = $q->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($r) => $this->summary($r))->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(RegionalManagerDeviceReturn $return)
    {
        $return->load(['fromRegionalManager', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json(['data' => $this->detail($return)]);
    }

    public function accept(Request $request, RegionalManagerDeviceReturn $return)
    {
        $validated = $request->validate(['note' => 'nullable|string|max:2000']);

        try {
            app(RegionalManagerDeviceReturnService::class)->acceptByAdmin(
                $return,
                $request->user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $return->load(['fromRegionalManager', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json([
            'message' => 'Return accepted. Devices are back in admin stock.',
            'data' => $this->detail($return),
        ]);
    }

    public function decline(Request $request, RegionalManagerDeviceReturn $return)
    {
        $validated = $request->validate(['note' => 'nullable|string|max:2000']);

        try {
            app(RegionalManagerDeviceReturnService::class)->declineByAdmin(
                $return,
                $request->user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $return->load(['fromRegionalManager', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json([
            'message' => 'Return request declined.',
            'data' => $this->detail($return),
        ]);
    }

    private function summary(RegionalManagerDeviceReturn $r): array
    {
        $isPending = $r->isPending();

        return [
            'id' => $r->id,
            'type' => 'regional_manager_to_admin',
            'status' => $r->status,
            'created_at' => $r->created_at?->toIso8601String(),
            'from_regional_manager' => $r->fromRegionalManager ? [
                'id' => $r->fromRegionalManager->id,
                'name' => $r->fromRegionalManager->name,
                'email' => $r->fromRegionalManager->email,
            ] : null,
            'items_count' => $r->items->count(),
            'message' => $r->message,
            'recipient_note' => $r->recipient_note,
            'direction' => 'incoming',
            'can_accept' => $isPending,
            'can_decline' => $isPending,
            'can_cancel' => false,
        ];
    }

    private function detail(RegionalManagerDeviceReturn $r): array
    {
        $base = $this->summary($r);
        $base['decided_at'] = $r->decided_at?->toIso8601String();
        $base['decided_by'] = $r->decidedByUser ? ['id' => $r->decidedByUser->id, 'name' => $r->decidedByUser->name] : null;
        $base['items'] = $r->items->map(function ($ri) {
            $i = $ri->productListItem;
            if (! $i) {
                return ['product_list_id' => $ri->product_list_id];
            }

            return [
                'product_list_id' => $i->id,
                'imei_number' => $i->imei_number,
                'model' => $i->model,
                'product' => $i->product ? [
                    'id' => $i->product->id,
                    'name' => $i->product->name,
                    'category' => $i->product->category?->name,
                ] : null,
            ];
        })->values()->all();

        return $base;
    }
}
