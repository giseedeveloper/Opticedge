<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegionalManagerProductTransfer;
use App\Services\RegionalManagerProductTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegionalManagerProductTransferApiController extends Controller
{
    public function index(Request $request)
    {
        $rmId = (int) Auth::id();
        $page = RegionalManagerProductTransfer::query()
            ->with(['createdByAdmin', 'toRegionalManager', 'items'])
            ->where('to_regional_manager_id', $rmId)
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($t) => $this->summary($t, $rmId))->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(RegionalManagerProductTransfer $transfer)
    {
        $rmId = (int) Auth::id();
        if ((int) $transfer->to_regional_manager_id !== $rmId) {
            abort(403);
        }

        $transfer->load([
            'createdByAdmin', 'toRegionalManager', 'decidedByUser',
            'items.productListItem.product.category',
        ]);

        return response()->json(['data' => $this->detail($transfer, $rmId)]);
    }

    public function accept(Request $request, RegionalManagerProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(RegionalManagerProductTransferService::class)->acceptByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $transfer->load([
            'createdByAdmin', 'toRegionalManager', 'decidedByUser',
            'items.productListItem.product.category',
        ]);

        return response()->json([
            'message' => 'Transfer accepted. Devices are now in your inventory.',
            'data' => $this->detail($transfer, (int) Auth::id()),
        ]);
    }

    public function decline(Request $request, RegionalManagerProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(RegionalManagerProductTransferService::class)->declineByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $transfer->load([
            'createdByAdmin', 'toRegionalManager', 'decidedByUser',
            'items.productListItem.product.category',
        ]);

        return response()->json([
            'message' => 'Transfer declined.',
            'data' => $this->detail($transfer, (int) Auth::id()),
        ]);
    }

    private function summary(RegionalManagerProductTransfer $t, ?int $viewerRmId = null): array
    {
        $viewerRmId ??= (int) Auth::id();
        $isIncoming = (int) $t->to_regional_manager_id === $viewerRmId;
        $isPending = $t->isPending();

        return [
            'id' => $t->id,
            'status' => $t->status,
            'created_at' => $t->created_at?->toIso8601String(),
            'from_admin' => $t->createdByAdmin ? [
                'id' => $t->createdByAdmin->id,
                'name' => $t->createdByAdmin->name,
                'email' => $t->createdByAdmin->email,
            ] : null,
            'to_regional_manager' => $t->toRegionalManager ? [
                'id' => $t->toRegionalManager->id,
                'name' => $t->toRegionalManager->name,
                'email' => $t->toRegionalManager->email,
            ] : null,
            'items_count' => $t->items->count(),
            'message' => $t->message,
            'admin_note' => $t->admin_note,
            'direction' => $isIncoming ? 'incoming' : null,
            'can_accept' => $isPending && $isIncoming,
            'can_decline' => $isPending && $isIncoming,
            'can_cancel' => false,
        ];
    }

    private function detail(RegionalManagerProductTransfer $t, ?int $viewerRmId = null): array
    {
        $viewerRmId ??= (int) Auth::id();
        $base = $this->summary($t, $viewerRmId);
        $base['decided_at'] = $t->decided_at?->toIso8601String();
        $base['decided_by'] = $t->decidedByUser ? ['id' => $t->decidedByUser->id, 'name' => $t->decidedByUser->name] : null;
        $base['items'] = $t->items->map(function ($ti) {
            $i = $ti->productListItem;
            if (! $i) {
                return ['product_list_id' => $ti->product_list_id];
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
