<?php

namespace App\Services;

use App\Models\AgentAssignment;
use App\Models\AgentProductListAssignment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegionalManagerDashboardService
{
    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function scopedTeamLeaderIds(int $regionalManagerId)
    {
        return User::query()
            ->where('role', 'teamleader')
            ->where('regional_manager_id', $regionalManagerId)
            ->pluck('id');
    }

    /**
     * Dashboard payload for regional manager overview (mirrors web dashboard).
     *
     * @return array<string, mixed>
     */
    public function build(User $manager): array
    {
        $manager->load(['branch', 'region']);

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

            return [
                'team_leader' => [
                    'id' => $tl->id,
                    'name' => $tl->name,
                    'email' => $tl->email,
                    'status' => $tl->status,
                    'branch_name' => $tl->branch?->name,
                ],
                'agent_count' => $tlAgents->count(),
                'active_agent_count' => $tlAgents->where('status', 'active')->count(),
                'qty_assigned' => $assigned,
                'qty_sold' => $sold,
                'qty_remaining' => max(0, $assigned - $sold),
                'imei_total' => (int) ($im->imei_total ?? 0),
                'imei_unsold' => (int) ($im->imei_unsold ?? 0),
                'imei_sold' => (int) ($im->imei_sold ?? 0),
            ];
        })->values()->all();

        $agentsPayload = $agents->map(function (User $agent) use ($assignmentTotals, $agentImeiStats) {
            $row = $assignmentTotals->get($agent->id);
            $im = $agentImeiStats->get($agent->id);
            $assigned = (int) ($row->assigned ?? 0);
            $sold = (int) ($row->sold ?? 0);

            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'status' => $agent->status,
                'branch_name' => $agent->branch?->name,
                'team_leader_id' => $agent->team_leader_id,
                'team_leader_name' => $agent->teamLeader?->name,
                'qty_assigned' => $assigned,
                'qty_sold' => $sold,
                'qty_remaining' => max(0, $assigned - $sold),
                'imei_total' => (int) ($im->imei_total ?? 0),
                'imei_unsold' => (int) ($im->imei_unsold ?? 0),
                'imei_sold' => (int) ($im->imei_sold ?? 0),
            ];
        })->values()->all();

        $productStatsPayload = $productImeiStats->map(fn ($p) => [
            'product_id' => (int) $p->product_id,
            'product_name' => $p->product_name ?: '—',
            'imei_total' => (int) $p->imei_total,
            'imei_unsold' => (int) $p->imei_unsold,
            'imei_sold' => (int) $p->imei_sold,
        ])->values()->all();

        return [
            'manager' => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'branch_name' => $manager->branch?->name,
                'region_name' => $manager->region?->name,
                'region_id' => $manager->region_id,
                'branches_represented' => $branchesRepresented,
            ],
            'stats' => [
                'team_leaders_count' => $teamLeaders->count(),
                'agents_count' => $agents->count(),
                'active_agents' => $activeAgents,
                'total_assigned' => $totalAssigned,
                'total_sold' => $totalSold,
                'total_remaining' => $totalRemaining,
                'total_imei_count' => $totalImeiCount,
                'unsold_imei_count' => $unsoldImeiCount,
                'sold_imei_count' => $soldImeiCount,
                'pending_sale_imei_count' => $pendingSaleImeiCount,
                'dealers_in_region' => $dealersInRegion,
                'customers_in_region' => $customersInRegion,
            ],
            'team_leader_rollups' => $teamLeaderRollups,
            'agents' => $agentsPayload,
            'product_imei_stats' => $productStatsPayload,
        ];
    }
}
