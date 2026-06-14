<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentDeviceReturn;
use App\Services\AgentDeviceReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentDeviceReturnApiController extends Controller
{
    public function index(Request $request)
    {
        $agentId = (int) Auth::id();
        $page = AgentDeviceReturn::query()
            ->with(['fromAgent', 'toTeamLeader', 'items'])
            ->where('from_agent_id', $agentId)
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($r) => $this->summary($r, $agentId))->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(AgentDeviceReturn $return)
    {
        $agentId = (int) Auth::id();
        if ((int) $return->from_agent_id !== $agentId) {
            abort(403);
        }

        $return->load(['fromAgent', 'toTeamLeader', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json(['data' => $this->detail($return, $agentId)]);
    }

    public function cancel(AgentDeviceReturn $return)
    {
        try {
            app(AgentDeviceReturnService::class)->cancelByAgent($return, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Return request cancelled.']);
    }

    private function summary(AgentDeviceReturn $r, ?int $viewerAgentId = null): array
    {
        $viewerAgentId ??= (int) Auth::id();
        $isOutgoing = (int) $r->from_agent_id === $viewerAgentId;
        $isPending = $r->isPending();

        return [
            'id' => $r->id,
            'status' => $r->status,
            'created_at' => $r->created_at?->toIso8601String(),
            'from_agent' => $r->fromAgent ? ['id' => $r->fromAgent->id, 'name' => $r->fromAgent->name, 'email' => $r->fromAgent->email] : null,
            'to_team_leader' => $r->toTeamLeader ? ['id' => $r->toTeamLeader->id, 'name' => $r->toTeamLeader->name, 'email' => $r->toTeamLeader->email] : null,
            'items_count' => $r->items->count(),
            'message' => $r->message,
            'recipient_note' => $r->recipient_note,
            'direction' => $isOutgoing ? 'outgoing' : 'incoming',
            'can_accept' => false,
            'can_decline' => false,
            'can_cancel' => $isPending && $isOutgoing,
        ];
    }

    private function detail(AgentDeviceReturn $r, ?int $viewerAgentId = null): array
    {
        $base = $this->summary($r, $viewerAgentId);
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
