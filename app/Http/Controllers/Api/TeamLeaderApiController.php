<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentProductListAssignment;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\User;
use App\Services\DeviceHierarchyAssignmentService;
use App\Support\AssignableImeiMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamLeaderApiController extends Controller
{
    /**
     * Team inventory / IMEI register (mirrors web team-inventory).
     */
    public function teamInventory(Request $request)
    {
        $agents = User::query()
            ->where('role', 'agent')
            ->where('team_leader_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'branch_id']);

        $agents->load('branch');

        $agentIds = $agents->pluck('id');

        $productTable = (new Product)->getTable();
        $productChoices = collect();

        if ($agentIds->isNotEmpty()) {
            $productChoices = DB::table('agent_product_list_assignments as apla')
                ->join('product_list as pl', 'pl.id', '=', 'apla.product_list_id')
                ->leftJoin("{$productTable} as p", 'p.id', '=', 'pl.product_id')
                ->whereIn('apla.agent_id', $agentIds)
                ->whereNotNull('pl.product_id')
                ->select('pl.product_id as id', DB::raw('MAX(p.name) as name'))
                ->groupBy('pl.product_id')
                ->orderBy('name')
                ->get();
        }

        $query = AgentProductListAssignment::query()
            ->whereIn('agent_id', $agentIds)
            ->with([
                'agent:id,name,email',
                'productListItem' => function ($q) {
                    $q->with(['product:id,name', 'branch:id,name', 'category:id,name']);
                },
            ]);

        if ($request->filled('agent_id')) {
            $aid = (int) $request->input('agent_id');
            if ($agentIds->contains($aid)) {
                $query->where('agent_id', $aid);
            }
        }

        if ($request->filled('product_id')) {
            $pid = (int) $request->input('product_id');
            $query->whereHas('productListItem', fn ($q) => $q->where('product_id', $pid));
        }

        $status = $request->input('status', 'all');
        if ($status === 'unsold') {
            $query->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'));
        } elseif ($status === 'sold') {
            $query->whereHas('productListItem', fn ($q) => $q->whereNotNull('sold_at'));
        } elseif ($status === 'pending') {
            $query->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at')->whereNotNull('pending_sale_id'));
        }

        if ($request->filled('q')) {
            $term = '%'.addcslashes($request->input('q'), '%_\\').'%';
            $query->whereHas('productListItem', fn ($q) => $q->where('imei_number', 'like', $term));
        }

        $perPage = min(max($request->integer('per_page', 35), 1), 100);
        $page = $agentIds->isEmpty()
            ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1)
            : $query->orderByDesc('id')->paginate($perPage);

        $summary = [
            'total' => 0,
            'unsold' => 0,
            'sold' => 0,
            'pending' => 0,
        ];
        if ($agentIds->isNotEmpty()) {
            $summary['total'] = (int) AgentProductListAssignment::query()->whereIn('agent_id', $agentIds)->count();
            $summary['unsold'] = (int) AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'))
                ->count();
            $summary['sold'] = (int) AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNotNull('sold_at'))
                ->count();
            $summary['pending'] = (int) AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at')->whereNotNull('pending_sale_id'))
                ->count();
        }

        return response()->json([
            'data' => [
                'summary' => $summary,
                'filters' => [
                    'agents' => $agents->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'branch_name' => $u->branch?->name,
                    ])->values()->all(),
                    'products' => $productChoices->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                    ])->values()->all(),
                    'status' => $status,
                ],
                'rows' => collect($page->items())->map(fn (AgentProductListAssignment $row) => $this->mapInventoryRow($row))->values()->all(),
                'meta' => [
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                ],
            ],
        ]);
    }

    public function assignAgentFormData()
    {
        $agents = User::query()
            ->where('role', 'agent')
            ->where('team_leader_id', Auth::id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $products = Product::inTeamLeaderCustodyForAgentAssignment((int) Auth::id())->get();

        return response()->json([
            'data' => [
                'agents' => $agents->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                ])->values()->all(),
                'products' => $products->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'category_name' => $p->category?->name,
                ])->values()->all(),
            ],
        ]);
    }

    public function assignableImeisForAgent(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
        ]);

        $items = ProductListItem::assignableToAgentByTeamLeader((int) $validated['product_id'], (int) Auth::id())
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json(['data' => AssignableImeiMatcher::mapRows($items)]);
    }

    public function validateAssignAgentImei(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:models,id',
            'imei' => 'required|string|max:512',
        ]);

        $query = ProductListItem::assignableToAgentByTeamLeader(
            (int) $validated['product_id'],
            (int) Auth::id()
        );

        $match = AssignableImeiMatcher::findMatch($query, $validated['imei']);

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

    public function storeAssignAgent(Request $request, DeviceHierarchyAssignmentService $hierarchyService)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:models,id',
            'product_list_ids' => 'required|array|min:1|max:500',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        $agent = User::findOrFail($validated['agent_id']);

        try {
            $count = $hierarchyService->assignToAgent(
                Auth::user(),
                $agent,
                (int) $validated['product_id'],
                $validated['product_list_ids']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $count === 1
                ? '1 device assigned to agent.'
                : "{$count} devices assigned to agent.",
            'data' => ['assigned_count' => $count],
        ], 201);
    }

    public function returnDevicesFormData()
    {
        $products = Product::returnableByTeamLeaderToRegionalManager((int) Auth::id())->get();

        return response()->json([
            'data' => [
                'products' => $products->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'category_name' => $p->category?->name,
                ])->values()->all(),
            ],
        ]);
    }

    public function returnableImeis(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
        ]);

        $items = ProductListItem::returnableByTeamLeader((int) $validated['product_id'], (int) Auth::id())
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json(['data' => AssignableImeiMatcher::mapRows($items)]);
    }

    public function storeReturnDevices(Request $request, DeviceHierarchyAssignmentService $hierarchyService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
            'product_list_ids' => 'required|array|min:1',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        try {
            $count = $hierarchyService->returnFromTeamLeaderToRegionalManager(
                Auth::user(),
                $validated['product_list_ids']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $count === 1
                ? '1 device returned to regional manager.'
                : "{$count} devices returned to regional manager.",
            'data' => ['returned_count' => $count],
        ]);
    }

    private function mapInventoryRow(AgentProductListAssignment $assignment): array
    {
        $pl = $assignment->productListItem;
        $status = 'unknown';
        if ($pl) {
            if ($pl->sold_at) {
                $status = 'sold';
            } elseif ($pl->pending_sale_id) {
                $status = 'pending';
            } else {
                $status = 'unsold';
            }
        }

        return [
            'id' => $assignment->id,
            'imei_number' => $pl?->imei_number,
            'model' => $pl?->model,
            'product' => $pl?->product ? [
                'id' => $pl->product->id,
                'name' => $pl->product->name,
            ] : null,
            'category' => $pl?->category ? [
                'id' => $pl->category->id,
                'name' => $pl->category->name,
            ] : null,
            'branch' => $pl?->branch ? [
                'id' => $pl->branch->id,
                'name' => $pl->branch->name,
            ] : null,
            'agent' => $assignment->agent ? [
                'id' => $assignment->agent->id,
                'name' => $assignment->agent->name,
                'email' => $assignment->agent->email,
            ] : null,
            'assigned_at' => $assignment->created_at?->toIso8601String(),
            'status' => $status,
            'sold_at' => $pl?->sold_at?->toIso8601String(),
        ];
    }
}
