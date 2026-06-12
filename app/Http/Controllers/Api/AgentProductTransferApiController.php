<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentProductTransfer;
use App\Models\ProductListItem;
use App\Services\AgentProductTransferService;
use App\Services\DeviceHierarchyAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentProductTransferApiController extends Controller
{
    public function index(Request $request)
    {
        $agentId = (int) Auth::id();
        $page = AgentProductTransfer::query()
            ->with(['fromAgent', 'toAgent', 'items'])
            ->where(function ($q) use ($agentId) {
                $q->where('from_agent_id', $agentId)->orWhere('to_agent_id', $agentId);
            })
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($t) => $this->summary($t, $agentId))->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(AgentProductTransfer $agent_product_transfer)
    {
        $agentId = (int) Auth::id();
        if (! in_array($agentId, [(int) $agent_product_transfer->from_agent_id, (int) $agent_product_transfer->to_agent_id], true)) {
            abort(403);
        }

        $agent_product_transfer->load([
            'fromAgent', 'toAgent', 'decidedByUser',
            'items.productListItem.product.category',
        ]);

        return response()->json(['data' => $this->detail($agent_product_transfer, $agentId)]);
    }

    public function cancel(AgentProductTransfer $agent_product_transfer)
    {
        try {
            app(AgentProductTransferService::class)->cancelOwn($agent_product_transfer, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Transfer cancelled.']);
    }

    public function accept(Request $request, AgentProductTransfer $agent_product_transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentProductTransferService::class)->acceptByRecipient(
                $agent_product_transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $agent_product_transfer->load(['fromAgent', 'toAgent', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json([
            'message' => 'Transfer accepted. Devices are now assigned to you.',
            'data' => $this->detail($agent_product_transfer, (int) Auth::id()),
        ]);
    }

    public function decline(Request $request, AgentProductTransfer $agent_product_transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentProductTransferService::class)->declineByRecipient(
                $agent_product_transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $agent_product_transfer->load(['fromAgent', 'toAgent', 'decidedByUser', 'items.productListItem.product.category']);

        return response()->json([
            'message' => 'Transfer declined.',
            'data' => $this->detail($agent_product_transfer, (int) Auth::id()),
        ]);
    }

    public function returnableImeis(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
        ]);

        $items = ProductListItem::returnableByAgent((int) $validated['product_id'], (int) Auth::id())
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json([
            'data' => $items->map(fn ($i) => [
                'id' => $i->id,
                'imei_number' => $i->imei_number,
                'model' => $i->model,
                'text' => $i->imei_number.($i->model ? ' – '.$i->model : ''),
            ])->values()->all(),
        ]);
    }

    public function returnToTeamLeader(Request $request, DeviceHierarchyAssignmentService $hierarchyService)
    {
        $validated = $request->validate([
            'product_list_ids' => 'required|array|min:1',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        try {
            $count = $hierarchyService->returnFromAgentToTeamLeader(
                Auth::user(),
                $validated['product_list_ids']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $count === 1
                ? '1 device returned to team leader.'
                : "{$count} devices returned to team leader.",
            'data' => ['returned_count' => $count],
        ]);
    }

    private function summary(AgentProductTransfer $t, ?int $viewerAgentId = null): array
    {
        $viewerAgentId ??= (int) Auth::id();
        $isIncoming = (int) $t->to_agent_id === $viewerAgentId;
        $isOutgoing = (int) $t->from_agent_id === $viewerAgentId;
        $isPending = $t->isPending();

        return [
            'id' => $t->id,
            'status' => $t->status,
            'created_at' => $t->created_at?->toIso8601String(),
            'from_agent' => $t->fromAgent ? ['id' => $t->fromAgent->id, 'name' => $t->fromAgent->name, 'email' => $t->fromAgent->email] : null,
            'to_agent' => $t->toAgent ? ['id' => $t->toAgent->id, 'name' => $t->toAgent->name, 'email' => $t->toAgent->email] : null,
            'items_count' => $t->items->count(),
            'message' => $t->message,
            'admin_note' => $t->admin_note,
            'direction' => $isIncoming ? 'incoming' : ($isOutgoing ? 'outgoing' : null),
            'can_accept' => $isPending && $isIncoming,
            'can_decline' => $isPending && $isIncoming,
            'can_cancel' => $isPending && $isOutgoing,
        ];
    }

    private function detail(AgentProductTransfer $t, ?int $viewerAgentId = null): array
    {
        $viewerAgentId ??= (int) Auth::id();
        $base = $this->summary($t, $viewerAgentId);
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
