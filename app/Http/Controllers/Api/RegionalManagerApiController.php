<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentProductListAssignment;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\User;
use App\Services\DeviceHierarchyAssignmentService;
use App\Services\TeamLeaderProductTransferService;
use App\Support\AssignableImeiMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegionalManagerApiController extends Controller
{
    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function scopedTeamLeaderIds(int $regionalManagerId)
    {
        return User::query()
            ->where('role', 'teamleader')
            ->where('regional_manager_id', $regionalManagerId)
            ->pluck('id');
    }

  /**
     * Regional inventory / IMEI register (mirrors web region-inventory).
     */
    public function regionInventory(Request $request)
    {
        $manager = Auth::user();
        $teamLeaderIds = $this->scopedTeamLeaderIds((int) $manager->id);

        $teamLeaders = User::query()
            ->whereIn('id', $teamLeaderIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $agents = $teamLeaderIds->isEmpty()
            ? collect()
            : User::query()
                ->where('role', 'agent')
                ->whereIn('team_leader_id', $teamLeaderIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'team_leader_id']);

        $agents->load('teamLeader:id,name');

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
                'agent' => function ($q) {
                    $q->select(['id', 'name', 'email', 'team_leader_id'])->with('teamLeader:id,name');
                },
                'productListItem' => function ($q) {
                    $q->with(['product:id,name', 'branch:id,name', 'category:id,name']);
                },
            ]);

        if ($request->filled('team_leader_id')) {
            $tlid = (int) $request->input('team_leader_id');
            if ($teamLeaderIds->contains($tlid)) {
                $agentSubIds = $agents->where('team_leader_id', $tlid)->pluck('id');
                $query->whereIn('agent_id', $agentSubIds);
            }
        }

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
                    'team_leaders' => $teamLeaders->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                    ])->values()->all(),
                    'agents' => $agents->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'team_leader_id' => $u->team_leader_id,
                        'team_leader_name' => $u->teamLeader?->name,
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

    public function assignTeamLeaderFormData()
    {
        $teamLeaders = User::query()
            ->where('role', 'teamleader')
            ->where('regional_manager_id', Auth::id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $products = Product::inRegionalManagerCustodyForTeamLeaderAssignment((int) Auth::id())->get();

        return response()->json([
            'data' => [
                'team_leaders' => $teamLeaders->map(fn ($u) => [
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

    public function assignableImeisForTeamLeader(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
        ]);

        $items = ProductListItem::assignableToTeamLeaderByRegionalManager((int) $validated['product_id'], (int) Auth::id())
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json(['data' => AssignableImeiMatcher::mapRows($items)]);
    }

    public function validateAssignTeamLeaderImei(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:models,id',
            'imei' => 'required|string|max:512',
        ]);

        $query = ProductListItem::assignableToTeamLeaderByRegionalManager(
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

    public function storeAssignTeamLeader(Request $request, TeamLeaderProductTransferService $transferService)
    {
        $validated = $request->validate([
            'team_leader_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:models,id',
            'product_list_ids' => 'required|array|min:1|max:500',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
            'message' => 'nullable|string|max:2000',
        ]);

        $teamLeader = User::findOrFail($validated['team_leader_id']);

        try {
            $transfer = $transferService->createByRegionalManager(
                Auth::user(),
                $teamLeader,
                (int) $validated['product_id'],
                $validated['product_list_ids'],
                $validated['message'] ?? null
            );
            $count = $transfer->items->count();
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $count === 1
                ? 'Transfer request sent to team leader (1 device).'
                : "Transfer request sent to team leader ({$count} devices).",
            'data' => ['assigned_count' => $count, 'transfer_id' => $transfer->id],
        ], 201);
    }

    public function returnDevicesFormData()
    {
        $products = Product::returnableByRegionalManagerToAdmin((int) Auth::id())->get();

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

        $items = ProductListItem::returnableByRegionalManager((int) $validated['product_id'], (int) Auth::id())
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
            $count = $hierarchyService->returnFromRegionalManagerToAdmin(
                Auth::user(),
                $validated['product_list_ids']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $count === 1
                ? '1 device returned to admin.'
                : "{$count} devices returned to admin.",
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
            'team_leader' => $assignment->agent?->teamLeader ? [
                'id' => $assignment->agent->teamLeader->id,
                'name' => $assignment->agent->teamLeader->name,
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
