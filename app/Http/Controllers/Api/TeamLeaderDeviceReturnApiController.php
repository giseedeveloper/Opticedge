<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentDeviceReturn;
use App\Models\TeamLeaderDeviceReturn;
use App\Services\AgentDeviceReturnService;
use App\Services\TeamLeaderDeviceReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamLeaderDeviceReturnApiController extends Controller
{
    public function indexIncoming(Request $request)
    {
        $tlId = (int) Auth::id();
        $page = AgentDeviceReturn::query()
            ->with(['fromAgent', 'toTeamLeader', 'items'])
            ->where('to_team_leader_id', $tlId)
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($r) => $this->agentReturnSummary($r, $tlId))->values()->all(),
            'meta' => $this->meta($page),
        ]);
    }

    public function indexOutgoing(Request $request)
    {
        $tlId = (int) Auth::id();
        $page = TeamLeaderDeviceReturn::query()
            ->with(['fromTeamLeader', 'toRegionalManager', 'items'])
            ->where('from_team_leader_id', $tlId)
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($r) => $this->tlReturnSummary($r, $tlId))->values()->all(),
            'meta' => $this->meta($page),
        ]);
    }

    public function showIncoming(AgentDeviceReturn $return)
    {
        $tlId = (int) Auth::id();
        if ((int) $return->to_team_leader_id !== $tlId) {
            abort(403);
        }

        $return->load(['fromAgent', 'toTeamLeader', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json(['data' => $this->agentReturnDetail($return, $tlId)]);
    }

    public function showOutgoing(TeamLeaderDeviceReturn $return)
    {
        $tlId = (int) Auth::id();
        if ((int) $return->from_team_leader_id !== $tlId) {
            abort(403);
        }

        $return->load(['fromTeamLeader', 'toRegionalManager', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json(['data' => $this->tlReturnDetail($return, $tlId)]);
    }

    public function acceptIncoming(Request $request, AgentDeviceReturn $return)
    {
        $validated = $request->validate(['note' => 'nullable|string|max:2000']);

        try {
            app(AgentDeviceReturnService::class)->acceptByRecipient($return, Auth::user(), $validated['note'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $return->load(['fromAgent', 'toTeamLeader', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json([
            'message' => 'Return accepted. Devices are back in your inventory.',
            'data' => $this->agentReturnDetail($return, (int) Auth::id()),
        ]);
    }

    public function declineIncoming(Request $request, AgentDeviceReturn $return)
    {
        $validated = $request->validate(['note' => 'nullable|string|max:2000']);

        try {
            app(AgentDeviceReturnService::class)->declineByRecipient($return, Auth::user(), $validated['note'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $return->load(['fromAgent', 'toTeamLeader', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json([
            'message' => 'Return request declined.',
            'data' => $this->agentReturnDetail($return, (int) Auth::id()),
        ]);
    }

    public function cancelOutgoing(TeamLeaderDeviceReturn $return)
    {
        try {
            app(TeamLeaderDeviceReturnService::class)->cancelByTeamLeader($return, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Return request cancelled.']);
    }

    private function agentReturnSummary(AgentDeviceReturn $r, int $tlId): array
    {
        $isIncoming = (int) $r->to_team_leader_id === $tlId;
        $isPending = $r->isPending();

        return [
            'id' => $r->id,
            'type' => 'agent_to_team_leader',
            'status' => $r->status,
            'created_at' => $r->created_at?->toIso8601String(),
            'from_agent' => $r->fromAgent ? ['id' => $r->fromAgent->id, 'name' => $r->fromAgent->name, 'email' => $r->fromAgent->email] : null,
            'to_team_leader' => $r->toTeamLeader ? ['id' => $r->toTeamLeader->id, 'name' => $r->toTeamLeader->name] : null,
            'items_count' => $r->items->count(),
            'message' => $r->message,
            'recipient_note' => $r->recipient_note,
            'direction' => $isIncoming ? 'incoming' : 'outgoing',
            'can_accept' => $isPending && $isIncoming,
            'can_decline' => $isPending && $isIncoming,
            'can_cancel' => false,
        ];
    }

    private function tlReturnSummary(TeamLeaderDeviceReturn $r, int $tlId): array
    {
        $isOutgoing = (int) $r->from_team_leader_id === $tlId;
        $isPending = $r->isPending();

        return [
            'id' => $r->id,
            'type' => 'team_leader_to_regional_manager',
            'status' => $r->status,
            'created_at' => $r->created_at?->toIso8601String(),
            'from_team_leader' => $r->fromTeamLeader ? ['id' => $r->fromTeamLeader->id, 'name' => $r->fromTeamLeader->name] : null,
            'to_regional_manager' => $r->toRegionalManager ? ['id' => $r->toRegionalManager->id, 'name' => $r->toRegionalManager->name, 'email' => $r->toRegionalManager->email] : null,
            'items_count' => $r->items->count(),
            'message' => $r->message,
            'recipient_note' => $r->recipient_note,
            'direction' => $isOutgoing ? 'outgoing' : 'incoming',
            'can_accept' => false,
            'can_decline' => false,
            'can_cancel' => $isPending && $isOutgoing,
        ];
    }

    private function agentReturnDetail(AgentDeviceReturn $r, int $tlId): array
    {
        $base = $this->agentReturnSummary($r, $tlId);
        $base['decided_at'] = $r->decided_at?->toIso8601String();
        $base['decided_by'] = $r->decidedByUser ? ['id' => $r->decidedByUser->id, 'name' => $r->decidedByUser->name] : null;
        $base['items'] = $this->mapItems($r->items);

        return $base;
    }

    private function tlReturnDetail(TeamLeaderDeviceReturn $r, int $tlId): array
    {
        $base = $this->tlReturnSummary($r, $tlId);
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
