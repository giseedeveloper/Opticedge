<?php

namespace App\Services;

use App\Models\AgentAssignment;
use App\Models\AgentProductListAssignment;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeamLeaderDashboardService
{
    /**
     * Dashboard payload for team leader overview (mirrors web dashboard).
     *
     * @return array<string, mixed>
     */
    public function build(User $leader): array
    {
        $leader->load(['branch', 'region', 'regionalManager:id,name']);

        $agents = User::query()
            ->where('role', 'agent')
            ->where('team_leader_id', $leader->id)
            ->with('branch')
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
        $productImeiStats = collect();

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

            $productTable = (new Product)->getTable();
            $productImeiStats = DB::table('agent_product_list_assignments as apla')
                ->join('product_list as pl', 'pl.id', '=', 'apla.product_list_id')
                ->leftJoin("{$productTable} as p", 'p.id', '=', 'pl.product_id')
                ->whereIn('apla.agent_id', $agentIds)
                ->selectRaw('pl.product_id, MAX(p.name) as product_name, COUNT(*) as imei_total, SUM(CASE WHEN pl.sold_at IS NULL THEN 1 ELSE 0 END) as imei_unsold, SUM(CASE WHEN pl.sold_at IS NOT NULL THEN 1 ELSE 0 END) as imei_sold')
                ->groupBy('pl.product_id')
                ->orderByDesc('imei_total')
                ->limit(12)
                ->get();
        }

        $custodyItems = ProductListItem::query()
            ->inTeamLeaderCustodyForAgentAssignment((int) $leader->id)
            ->with('product:id,name')
            ->get(['id', 'product_id']);

        $devicesInHandCount = $custodyItems->count();
        $custodyProductStats = $this->groupCustodyItemsByProduct($custodyItems);

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
            'leader' => [
                'id' => $leader->id,
                'name' => $leader->name,
                'email' => $leader->email,
                'branch_name' => $leader->branch?->name,
                'region_name' => $leader->region?->name,
                'regional_manager_name' => $leader->regionalManager?->name,
            ],
            'stats' => [
                'agents_count' => $agents->count(),
                'active_agents' => $activeAgents,
                'devices_in_hand_count' => $devicesInHandCount,
                'total_assigned' => $totalAssigned,
                'total_sold' => $totalSold,
                'total_remaining' => $totalRemaining,
                'total_imei_count' => $totalImeiCount,
                'unsold_imei_count' => $unsoldImeiCount,
                'sold_imei_count' => $soldImeiCount,
                'pending_sale_imei_count' => $pendingSaleImeiCount,
            ],
            'agents' => $agentsPayload,
            'product_imei_stats' => $productStatsPayload,
            'custody_product_stats' => $custodyProductStats,
        ];
    }

    /**
     * @param  Collection<int, ProductListItem>  $items
     * @return list<array{product_id: int, product_name: string, device_count: int}>
     */
    private function groupCustodyItemsByProduct(Collection $items): array
    {
        return $items
            ->groupBy(fn (ProductListItem $item) => $item->product_id ?? 0)
            ->map(function (Collection $group, $productId) {
                $product = $group->first()->product;

                return [
                    'product_id' => (int) $productId,
                    'product_name' => $product?->name ?? '—',
                    'device_count' => $group->count(),
                ];
            })
            ->sortByDesc('device_count')
            ->values()
            ->all();
    }
}
