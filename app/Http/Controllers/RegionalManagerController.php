<?php

namespace App\Http\Controllers;

use App\Models\AgentAssignment;
use App\Models\AgentProductListAssignment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegionalManagerController extends Controller
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

    public function dashboard()
    {
        $manager = Auth::user()->load(['branch', 'region']);

        $teamLeaderIds = $this->scopedTeamLeaderIds((int) $manager->id);

        $teamLeaders = User::query()
            ->whereIn('id', $teamLeaderIds)
            ->with('branch')
            ->orderBy('name')
            ->get();

        $agents = $teamLeaderIds->isEmpty()
            ? collect()
            : User::query()
                ->where('role', 'agent')
                ->whereIn('team_leader_id', $teamLeaderIds)
                ->with(['branch', 'teamLeader:id,name'])
                ->orderBy('name')
                ->get();

        $agentIds = $agents->pluck('id');

        $assignmentTotals = $agentIds->isEmpty()
            ? collect()
            : AgentAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->selectRaw('agent_id, SUM(quantity_assigned) as assigned, SUM(quantity_sold) as sold')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');

        $totalAssigned = (int) $assignmentTotals->sum('assigned');
        $totalSold = (int) $assignmentTotals->sum('sold');
        $totalRemaining = max(0, $totalAssigned - $totalSold);
        $activeAgents = $agents->where('status', 'active')->count();

        $unsoldImeiCount = 0;
        $soldImeiCount = 0;
        $totalImeiCount = 0;
        $pendingSaleImeiCount = 0;
        $agentImeiStats = collect();
        $teamLeaderImeiStats = collect();
        $productImeiStats = collect();

        $usersTable = (new User)->getTable();

        if ($agentIds->isNotEmpty()) {
            $unsoldImeiCount = AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'))
                ->count();

            $soldImeiCount = AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNotNull('sold_at'))
                ->count();

            $totalImeiCount = $unsoldImeiCount + $soldImeiCount;

            $pendingSaleImeiCount = AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', function ($q) {
                    $q->whereNull('sold_at')->whereNotNull('pending_sale_id');
                })
                ->count();

            $agentImeiStats = AgentProductListAssignment::query()
                ->whereIn('agent_product_list_assignments.agent_id', $agentIds)
                ->join('product_list as pl', 'pl.id', '=', 'agent_product_list_assignments.product_list_id')
                ->selectRaw('agent_product_list_assignments.agent_id, COUNT(*) as imei_total, SUM(CASE WHEN pl.sold_at IS NULL THEN 1 ELSE 0 END) as imei_unsold, SUM(CASE WHEN pl.sold_at IS NOT NULL THEN 1 ELSE 0 END) as imei_sold')
                ->groupBy('agent_product_list_assignments.agent_id')
                ->get()
                ->keyBy('agent_id');

            $teamLeaderImeiStats = DB::table('agent_product_list_assignments as apla')
                ->join('product_list as pl', 'pl.id', '=', 'apla.product_list_id')
                ->join("{$usersTable} as ag", 'ag.id', '=', 'apla.agent_id')
                ->whereIn('apla.agent_id', $agentIds)
                ->whereIn('ag.team_leader_id', $teamLeaderIds)
                ->selectRaw('ag.team_leader_id, COUNT(*) as imei_total, SUM(CASE WHEN pl.sold_at IS NULL THEN 1 ELSE 0 END) as imei_unsold, SUM(CASE WHEN pl.sold_at IS NOT NULL THEN 1 ELSE 0 END) as imei_sold')
                ->groupBy('ag.team_leader_id')
                ->get()
                ->keyBy('team_leader_id');

            $productTable = (new Product)->getTable();
            $productImeiStats = DB::table('agent_product_list_assignments as apla')
                ->join('product_list as pl', 'pl.id', '=', 'apla.product_list_id')
                ->leftJoin("{$productTable} as p", 'p.id', '=', 'pl.product_id')
                ->whereIn('apla.agent_id', $agentIds)
                ->selectRaw('pl.product_id, MAX(p.name) as product_name, COUNT(*) as imei_total, SUM(CASE WHEN pl.sold_at IS NULL THEN 1 ELSE 0 END) as imei_unsold, SUM(CASE WHEN pl.sold_at IS NOT NULL THEN 1 ELSE 0 END) as imei_sold')
                ->groupBy('pl.product_id')
                ->orderByDesc('imei_total')
                ->limit(24)
                ->get();
        }

        $dealersInRegion = 0;
        $customersInRegion = 0;
        if ($manager->region_id) {
            $dealersInRegion = (int) User::query()
                ->where('region_id', $manager->region_id)
                ->where('role', 'dealer')
                ->where('status', 'active')
                ->count();

            $customersInRegion = (int) User::query()
                ->where('region_id', $manager->region_id)
                ->where('role', 'customer')
                ->where('status', 'active')
                ->count();
        }

        $branchesRepresented = $agents
            ->merge($teamLeaders)
            ->pluck('branch_id')
            ->filter()
            ->unique()
            ->count();

        /** @var \Illuminate\Support\Collection<int, object> $teamLeaderRollups */
        $teamLeaderRollups = $teamLeaders->map(function (User $tl) use ($agents, $assignmentTotals, $teamLeaderImeiStats) {
            $tlAgents = $agents->where('team_leader_id', $tl->id);
            $tlAgentIds = $tlAgents->pluck('id');
            $assigned = 0;
            $sold = 0;
            foreach ($tlAgentIds as $aid) {
                $row = $assignmentTotals->get($aid);
                $assigned += (int) ($row->assigned ?? 0);
                $sold += (int) ($row->sold ?? 0);
            }
            $im = $teamLeaderImeiStats->get($tl->id);

            return (object) [
                'team_leader' => $tl,
                'agent_count' => $tlAgents->count(),
                'active_agent_count' => $tlAgents->where('status', 'active')->count(),
                'qty_assigned' => $assigned,
                'qty_sold' => $sold,
                'qty_remaining' => max(0, $assigned - $sold),
                'imei_total' => (int) ($im->imei_total ?? 0),
                'imei_unsold' => (int) ($im->imei_unsold ?? 0),
                'imei_sold' => (int) ($im->imei_sold ?? 0),
            ];
        });

        return view('regional-manager.dashboard', compact(
            'manager',
            'teamLeaders',
            'teamLeaderRollups',
            'agents',
            'assignmentTotals',
            'agentImeiStats',
            'productImeiStats',
            'totalAssigned',
            'totalSold',
            'totalRemaining',
            'activeAgents',
            'unsoldImeiCount',
            'soldImeiCount',
            'totalImeiCount',
            'pendingSaleImeiCount',
            'dealersInRegion',
            'customersInRegion',
            'branchesRepresented'
        ));
    }

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

        $rows = $agentIds->isEmpty()
            ? new LengthAwarePaginator([], 0, 35, 1, ['path' => $request->url(), 'query' => $request->query()])
            : $query->orderByDesc('id')->paginate(35)->withQueryString();

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

        $filterStatus = $status;

        return view('regional-manager.region-inventory', compact(
            'teamLeaders',
            'agents',
            'rows',
            'productChoices',
            'summary',
            'filterStatus'
        ));
    }

    public function profile()
    {
        return view('regional-manager.profile');
    }
}
