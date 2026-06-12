<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamLeaderProductTransfer;
use App\Services\TeamLeaderProductTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamLeaderProductTransferApiController extends Controller
{
    public function index(Request $request)
    {
        $tlId = (int) Auth::id();
        $page = TeamLeaderProductTransfer::query()
            ->with(['fromRegionalManager', 'toTeamLeader', 'items'])
            ->where('to_team_leader_id', $tlId)
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($t) => $this->summary($t, $tlId))->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(TeamLeaderProductTransfer $transfer)
    {
        $tlId = (int) Auth::id();
        if ((int) $transfer->to_team_leader_id !== $tlId) {
            abort(403);
        }

        $transfer->load([
            'fromRegionalManager', 'toTeamLeader', 'decidedByUser',
            'items.productListItem.product.category',
        ]);

        return response()->json(['data' => $this->detail($transfer, $tlId)]);
    }

    public function accept(Request $request, TeamLeaderProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(TeamLeaderProductTransferService::class)->acceptByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $transfer->load([
            'fromRegionalManager', 'toTeamLeader', 'decidedByUser',
            'items.productListItem.product.category',
        ]);

        return response()->json([
            'message' => 'Transfer accepted. Devices are now in your inventory.',
            'data' => $this->detail($transfer, (int) Auth::id()),
        ]);
    }

    public function decline(Request $request, TeamLeaderProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(TeamLeaderProductTransferService::class)->declineByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $transfer->load([
            'fromRegionalManager', 'toTeamLeader', 'decidedByUser',
            'items.productListItem.product.category',
        ]);

        return response()->json([
            'message' => 'Transfer declined.',
            'data' => $this->detail($transfer, (int) Auth::id()),
        ]);
    }

    private function summary(TeamLeaderProductTransfer $t, ?int $viewerTlId = null): array
    {
        $viewerTlId ??= (int) Auth::id();
        $isIncoming = (int) $t->to_team_leader_id === $viewerTlId;
        $isPending = $t->isPending();

        return [
            'id' => $t->id,
            'status' => $t->status,
            'created_at' => $t->created_at?->toIso8601String(),
            'from_regional_manager' => $t->fromRegionalManager ? [
                'id' => $t->fromRegionalManager->id,
                'name' => $t->fromRegionalManager->name,
                'email' => $t->fromRegionalManager->email,
            ] : null,
            'to_team_leader' => $t->toTeamLeader ? [
                'id' => $t->toTeamLeader->id,
                'name' => $t->toTeamLeader->name,
                'email' => $t->toTeamLeader->email,
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

    private function detail(TeamLeaderProductTransfer $t, ?int $viewerTlId = null): array
    {
        $viewerTlId ??= (int) Auth::id();
        $base = $this->summary($t, $viewerTlId);
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
