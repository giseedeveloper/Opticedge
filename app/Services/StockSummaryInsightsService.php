<?php

namespace App\Services;

use App\Models\AgentCredit;
use App\Models\AgentProductListAssignment;
use App\Models\AgentSale;
use App\Models\PendingSale;
use App\Models\ProductListItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StockSummaryInsightsService
{
    /**
     * Unsold IMEI counts by custody role + agent aging / low-stock alert counts.
     *
     * @return array{
     *     inventory: array{admin:int,regional_managers:int,team_leaders:int,agents:int,total:int},
     *     aging: array{days7:int,days14:int},
     *     low_stock: array{count:int,threshold:int}
     * }
     */
    public function summaryCounts(): array
    {
        $inventory = $this->inventoryByRole();
        $unsoldByAgent = $this->unsoldStockByAgent();
        $agentIdsWithStock = $unsoldByAgent->keys()->all();

        return [
            'inventory' => $inventory,
            'aging' => [
                'days7' => $this->agingAgentIds(7, $agentIdsWithStock)->count(),
                'days14' => $this->agingAgentIds(14, $agentIdsWithStock)->count(),
            ],
            'low_stock' => [
                'count' => $this->lowStockAgentIds($unsoldByAgent)->count(),
                'threshold' => 2,
            ],
        ];
    }

    /**
     * @return array{admin:int,regional_managers:int,team_leaders:int,agents:int,total:int}
     */
    public function inventoryByRole(): array
    {
        if (! Schema::hasTable('product_list')) {
            return [
                'admin' => 0,
                'regional_managers' => 0,
                'team_leaders' => 0,
                'agents' => 0,
                'total' => 0,
            ];
        }

        $admin = ProductListItem::query()
            ->whereNull('sold_at')
            ->whereDoesntHave('regionalManagerProductListAssignment')
            ->whereDoesntHave('teamLeaderProductListAssignment')
            ->whereDoesntHave('agentProductListAssignment')
            ->count();

        $regionalManagers = ProductListItem::query()
            ->whereNull('sold_at')
            ->whereHas('regionalManagerProductListAssignment')
            ->whereDoesntHave('teamLeaderProductListAssignment')
            ->whereDoesntHave('agentProductListAssignment')
            ->count();

        $teamLeaders = ProductListItem::query()
            ->whereNull('sold_at')
            ->whereHas('teamLeaderProductListAssignment')
            ->whereDoesntHave('agentProductListAssignment')
            ->count();

        $agents = ProductListItem::query()
            ->whereNull('sold_at')
            ->whereHas('agentProductListAssignment')
            ->count();

        return [
            'admin' => $admin,
            'regional_managers' => $regionalManagers,
            'team_leaders' => $teamLeaders,
            'agents' => $agents,
            'total' => $admin + $regionalManagers + $teamLeaders + $agents,
        ];
    }

    /**
     * Stock-in-hand matrix: rows = device model, columns = current holder
     * (Admin warehouse / Regional Manager / Team Leader / Agent), cell = quantity.
     *
     * Holder is derived the same way as {@see inventoryByRole()}: an unsold device
     * is "with" the deepest hierarchy level it has an assignment row for (agent >
     * team leader > regional manager), otherwise it is still in the admin warehouse.
     *
     * @return array{
     *     models: list<string>,
     *     holders: list<array{key:string, role:string, id:?int, label:string}>,
     *     matrix: array<string, array<string, int>>,
     *     row_totals: array<string, int>,
     *     column_totals: array<string, int>,
     *     grand_total: int,
     * }
     */
    public function stockInHandMatrix(): array
    {
        $empty = [
            'models' => [],
            'holders' => [],
            'matrix' => [],
            'row_totals' => [],
            'column_totals' => [],
            'grand_total' => 0,
        ];

        if (! Schema::hasTable('product_list')) {
            return $empty;
        }

        // Admin warehouse: unsold devices with no hierarchy assignment at all.
        $adminRows = ProductListItem::query()
            ->whereNull('sold_at')
            ->whereDoesntHave('regionalManagerProductListAssignment')
            ->whereDoesntHave('teamLeaderProductListAssignment')
            ->whereDoesntHave('agentProductListAssignment')
            ->selectRaw('product_list.model as model_name, COUNT(*) as qty')
            ->groupBy('product_list.model')
            ->get();

        // Regional manager: has an RM assignment, but not yet passed down to a TL/agent.
        $regionalManagerRows = Schema::hasTable('regional_manager_product_list_assignments')
            ? ProductListItem::query()
                ->join('regional_manager_product_list_assignments as rmpla', 'rmpla.product_list_id', '=', 'product_list.id')
                ->whereNull('product_list.sold_at')
                ->whereDoesntHave('teamLeaderProductListAssignment')
                ->whereDoesntHave('agentProductListAssignment')
                ->selectRaw('rmpla.regional_manager_id as holder_id, product_list.model as model_name, COUNT(*) as qty')
                ->groupBy('rmpla.regional_manager_id', 'product_list.model')
                ->get()
            : collect();

        // Team leader: has a TL assignment, but not yet passed down to an agent.
        $teamLeaderRows = Schema::hasTable('team_leader_product_list_assignments')
            ? ProductListItem::query()
                ->join('team_leader_product_list_assignments as tlpla', 'tlpla.product_list_id', '=', 'product_list.id')
                ->whereNull('product_list.sold_at')
                ->whereDoesntHave('agentProductListAssignment')
                ->selectRaw('tlpla.team_leader_id as holder_id, product_list.model as model_name, COUNT(*) as qty')
                ->groupBy('tlpla.team_leader_id', 'product_list.model')
                ->get()
            : collect();

        // Agent: has an agent assignment (terminal end of the hierarchy).
        $agentRows = ProductListItem::query()
            ->join('agent_product_list_assignments as apla', 'apla.product_list_id', '=', 'product_list.id')
            ->whereNull('product_list.sold_at')
            ->selectRaw('apla.agent_id as holder_id, product_list.model as model_name, COUNT(*) as qty')
            ->groupBy('apla.agent_id', 'product_list.model')
            ->get();

        $userIds = collect()
            ->merge($regionalManagerRows->pluck('holder_id'))
            ->merge($teamLeaderRows->pluck('holder_id'))
            ->merge($agentRows->pluck('holder_id'))
            ->filter()
            ->unique()
            ->values();

        $userNames = $userIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $userIds->all())->pluck('name', 'id');

        $matrix = [];
        $models = [];
        $holders = [
            'admin' => ['key' => 'admin', 'role' => 'admin', 'id' => null, 'label' => 'Admin / Warehouse'],
        ];

        $applyRows = function (Collection $rows, string $role, string $keyPrefix, ?string $labelPrefix) use (&$matrix, &$models, &$holders, $userNames) {
            foreach ($rows as $row) {
                $model = (string) ($row->model_name ?: 'Unspecified model');
                $qty = (int) $row->qty;

                if ($labelPrefix === null) {
                    $holderKey = 'admin';
                } else {
                    $holderId = (int) $row->holder_id;
                    $holderKey = $keyPrefix.'_'.$holderId;
                    if (! isset($holders[$holderKey])) {
                        $name = $userNames[$holderId] ?? ($labelPrefix.' #'.$holderId);
                        $holders[$holderKey] = [
                            'key' => $holderKey,
                            'role' => $role,
                            'id' => $holderId,
                            'label' => $labelPrefix.': '.$name,
                        ];
                    }
                }

                $models[$model] = true;
                $matrix[$model][$holderKey] = ($matrix[$model][$holderKey] ?? 0) + $qty;
            }
        };

        $applyRows($adminRows, 'admin', 'admin', null);
        $applyRows($regionalManagerRows, 'regional_manager', 'rm', 'Regional manager');
        $applyRows($teamLeaderRows, 'team_leader', 'tl', 'Team leader');
        $applyRows($agentRows, 'agent', 'agent', 'Agent');

        $modelList = collect(array_keys($models))->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        // Order holder columns: admin first, then RM, then TL, then agents (alphabetically within each group).
        $holderOrder = ['admin', 'regional_manager', 'team_leader', 'agent'];
        $holderList = collect($holders)
            ->sortBy(fn ($h) => [array_search($h['role'], $holderOrder, true), $h['label']])
            ->values()
            ->all();

        $rowTotals = [];
        $columnTotals = [];
        $grandTotal = 0;

        foreach ($modelList as $model) {
            $rowTotal = 0;
            foreach ($holderList as $holder) {
                $qty = $matrix[$model][$holder['key']] ?? 0;
                $matrix[$model][$holder['key']] = $qty;
                $rowTotal += $qty;
                $columnTotals[$holder['key']] = ($columnTotals[$holder['key']] ?? 0) + $qty;
            }
            $rowTotals[$model] = $rowTotal;
            $grandTotal += $rowTotal;
        }

        return [
            'models' => $modelList,
            'holders' => $holderList,
            'matrix' => $matrix,
            'row_totals' => $rowTotals,
            'column_totals' => $columnTotals,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * agent_id => unsold IMEI count (only agents with at least 1 unsold device).
     *
     * @return Collection<int, int>
     */
    public function unsoldStockByAgent(): Collection
    {
        if (! Schema::hasTable('agent_product_list_assignments') || ! Schema::hasTable('product_list')) {
            return collect();
        }

        return AgentProductListAssignment::query()
            ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'))
            ->selectRaw('agent_id, COUNT(*) as unsold_count')
            ->groupBy('agent_id')
            ->pluck('unsold_count', 'agent_id')
            ->map(fn ($count) => (int) $count);
    }

    /**
     * Agents holding unsold stock who have not sold anything in the last N days.
     *
     * @param  list<int>  $agentIdsWithStock
     * @return Collection<int, int> agent ids
     */
    public function agingAgentIds(int $days, ?array $agentIdsWithStock = null): Collection
    {
        $agentIdsWithStock ??= $this->unsoldStockByAgent()->keys()->all();
        if ($agentIdsWithStock === []) {
            return collect();
        }

        $since = Carbon::now()->subDays($days)->startOfDay();
        $activeSellerIds = $this->agentIdsWithSalesSince($since);

        return collect($agentIdsWithStock)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => in_array($id, $activeSellerIds, true))
            ->values();
    }

    /**
     * Agents with unsold stock count between 1 and threshold (inclusive).
     *
     * @param  Collection<int, int>|null  $unsoldByAgent
     * @return Collection<int, int> agent ids
     */
    public function lowStockAgentIds(?Collection $unsoldByAgent = null, int $threshold = 2): Collection
    {
        $unsoldByAgent ??= $this->unsoldStockByAgent();

        return $unsoldByAgent
            ->filter(fn (int $count) => $count > 0 && $count <= $threshold)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @param  'aging7'|'aging14'|'low'  $filter
     * @return Collection<int, object>
     */
    public function agentsForFilter(string $filter): Collection
    {
        $unsoldByAgent = $this->unsoldStockByAgent();

        $agentIds = match ($filter) {
            'aging7' => $this->agingAgentIds(7, $unsoldByAgent->keys()->all()),
            'aging14' => $this->agingAgentIds(14, $unsoldByAgent->keys()->all()),
            'low' => $this->lowStockAgentIds($unsoldByAgent),
            default => collect(),
        };

        if ($agentIds->isEmpty()) {
            return collect();
        }

        $users = User::query()
            ->where('role', 'agent')
            ->whereIn('id', $agentIds->all())
            ->with(['teamLeader:id,name', 'branch:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'status', 'team_leader_id', 'branch_id']);

        $lastSaleByAgent = $this->lastSaleDatesForAgents($agentIds->all());

        return $users->map(function (User $agent) use ($unsoldByAgent, $lastSaleByAgent) {
            $lastSale = $lastSaleByAgent[$agent->id] ?? null;

            return (object) [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'phone' => $agent->phone,
                'status' => $agent->status,
                'team_leader' => $agent->teamLeader?->name,
                'branch' => $agent->branch?->name,
                'unsold_stock' => (int) ($unsoldByAgent[$agent->id] ?? 0),
                'last_sale_at' => $lastSale,
                'days_since_sale' => $lastSale
                    ? (int) Carbon::parse($lastSale)->startOfDay()->diffInDays(Carbon::now()->startOfDay())
                    : null,
            ];
        });
    }

    /**
     * @return list<int>
     */
    public function agentIdsWithSalesSince(Carbon $since): array
    {
        $ids = collect();

        if (Schema::hasTable('agent_sales')) {
            $ids = $ids->merge(
                AgentSale::query()
                    ->whereNotNull('agent_id')
                    ->where('date', '>=', $since)
                    ->distinct()
                    ->pluck('agent_id')
            );
        }

        if (Schema::hasTable('pending_sales')) {
            $ids = $ids->merge(
                PendingSale::query()
                    ->whereNotNull('seller_id')
                    ->where('date', '>=', $since)
                    ->distinct()
                    ->pluck('seller_id')
            );
        }

        if (Schema::hasTable('agent_credits')) {
            $ids = $ids->merge(
                AgentCredit::query()
                    ->whereNotNull('agent_id')
                    ->where('date', '>=', $since)
                    ->distinct()
                    ->pluck('agent_id')
            );
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * @param  list<int>  $agentIds
     * @return array<int, string> agent_id => Y-m-d H:i:s
     */
    public function lastSaleDatesForAgents(array $agentIds): array
    {
        if ($agentIds === []) {
            return [];
        }

        $dates = [];

        $remember = function (int $agentId, $date) use (&$dates): void {
            if (! $date) {
                return;
            }
            $value = Carbon::parse($date)->toDateTimeString();
            if (! isset($dates[$agentId]) || $value > $dates[$agentId]) {
                $dates[$agentId] = $value;
            }
        };

        if (Schema::hasTable('agent_sales')) {
            AgentSale::query()
                ->whereIn('agent_id', $agentIds)
                ->selectRaw('agent_id, MAX(date) as last_date')
                ->groupBy('agent_id')
                ->get()
                ->each(fn ($row) => $remember((int) $row->agent_id, $row->last_date));
        }

        if (Schema::hasTable('pending_sales')) {
            PendingSale::query()
                ->whereIn('seller_id', $agentIds)
                ->selectRaw('seller_id, MAX(date) as last_date')
                ->groupBy('seller_id')
                ->get()
                ->each(fn ($row) => $remember((int) $row->seller_id, $row->last_date));
        }

        if (Schema::hasTable('agent_credits')) {
            AgentCredit::query()
                ->whereIn('agent_id', $agentIds)
                ->selectRaw('agent_id, MAX(date) as last_date')
                ->groupBy('agent_id')
                ->get()
                ->each(fn ($row) => $remember((int) $row->agent_id, $row->last_date));
        }

        return $dates;
    }
}
