<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegionalManagerDeviceReturn;
use App\Models\TeamLeaderDeviceReturn;
use App\Services\RegionalManagerDeviceReturnService;
use App\Services\TeamLeaderDeviceReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegionalManagerDeviceReturnApiController extends Controller
{
    public function indexIncoming(Request $request)
    {
        $rmId = (int) Auth::id();
        $page = TeamLeaderDeviceReturn::query()
            ->with(['fromTeamLeader', 'toRegionalManager', 'items'])
            ->where('to_regional_manager_id', $rmId)
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($r) => $this->tlReturnSummary($r, $rmId))->values()->all(),
            'meta' => $this->meta($page),
        ]);
    }

    public function indexOutgoing(Request $request)
    {
        $rmId = (int) Auth::id();
        $page = RegionalManagerDeviceReturn::query()
            ->with(['fromRegionalManager', 'items'])
            ->where('from_regional_manager_id', $rmId)
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($r) => $this->rmReturnSummary($r, $rmId))->values()->all(),
            'meta' => $this->meta($page),
        ]);
    }

    public function acceptIncoming(Request $request, TeamLeaderDeviceReturn $return)
    {
        $validated = $request->validate(['note' => 'nullable|string|max:2000']);

        try {
            app(TeamLeaderDeviceReturnService::class)->acceptByRecipient($return, Auth::user(), $validated['note'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $return->load(['fromTeamLeader', 'toRegionalManager', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json([
            'message' => 'Return accepted. Devices are back in your inventory.',
            'data' => $this->tlReturnDetail($return, (int) Auth::id()),
        ]);
    }

    public function declineIncoming(Request $request, TeamLeaderDeviceReturn $return)
    {
        $validated = $request->validate(['note' => 'nullable|string|max:2000']);

        try {
            app(TeamLeaderDeviceReturnService::class)->declineByRecipient($return, Auth::user(), $validated['note'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $return->load(['fromTeamLeader', 'toRegionalManager', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json([
            'message' => 'Return request declined.',
            'data' => $this->tlReturnDetail($return, (int) Auth::id()),
        ]);
    }

    public function cancelOutgoing(RegionalManagerDeviceReturn $return)
    {
        try {
            app(RegionalManagerDeviceReturnService::class)->cancelByRegionalManager($return, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Return request cancelled.']);
    }

    private function tlReturnSummary(TeamLeaderDeviceReturn $r, int $rmId): array
    {
        $isIncoming = (int) $r->to_regional_manager_id === $rmId;
        $isPending = $r->isPending();

        return [
            'id' => $r->id,
            'type' => 'team_leader_to_regional_manager',
            'status' => $r->status,
            'created_at' => $r->created_at?->toIso8601String(),
            'from_team_leader' => $r->fromTeamLeader ? ['id' => $r->fromTeamLeader->id, 'name' => $r->fromTeamLeader->name, 'email' => $r->fromTeamLeader->email] : null,
            'to_regional_manager' => $r->toRegionalManager ? ['id' => $r->toRegionalManager->id, 'name' => $r->toRegionalManager->name] : null,
            'items_count' => $r->items->count(),
            'message' => $r->message,
            'recipient_note' => $r->recipient_note,
            'direction' => $isIncoming ? 'incoming' : 'outgoing',
            'can_accept' => $isPending && $isIncoming,
            'can_decline' => $isPending && $isIncoming,
            'can_cancel' => false,
        ];
    }

    private function rmReturnSummary(RegionalManagerDeviceReturn $r, int $rmId): array
    {
        $isOutgoing = (int) $r->from_regional_manager_id === $rmId;
        $isPending = $r->isPending();

        return [
            'id' => $r->id,
            'type' => 'regional_manager_to_admin',
            'status' => $r->status,
            'created_at' => $r->created_at?->toIso8601String(),
            'from_regional_manager' => $r->fromRegionalManager ? ['id' => $r->fromRegionalManager->id, 'name' => $r->fromRegionalManager->name] : null,
            'items_count' => $r->items->count(),
            'message' => $r->message,
            'recipient_note' => $r->recipient_note,
            'direction' => $isOutgoing ? 'outgoing' : 'incoming',
            'can_accept' => false,
            'can_decline' => false,
            'can_cancel' => $isPending && $isOutgoing,
        ];
    }

    private function tlReturnDetail(TeamLeaderDeviceReturn $r, int $rmId): array
    {
        $base = $this->tlReturnSummary($r, $rmId);
        $base['decided_at'] = $r->decided_at?->toIso8601String();
        $base['decided_by'] = $r->decidedByUser ? ['id' => $r->decidedByUser->id, 'name' => $r->decidedByUser->name] : null;
        $base['items'] = $this->mapItems($r->items);

        return $base;
    }

    private function mapItems($items): array
    {
        return $items->map(function ($ri) {
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
    }

    private function meta($page): array
    {
        return [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ];
    }
}
