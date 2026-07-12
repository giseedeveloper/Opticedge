<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SalesReportSummaryInsightsService
{
    private const KNOWN_ROLES = ['admin', 'teamleader', 'regional_manager', 'agent'];

    public function __construct(
        private readonly StockSummaryInsightsService $stockInsights,
    ) {}

    /**
     * @return array{
     *     admin:int,
     *     team_leaders:int,
     *     regional_managers:int,
     *     agents_active:int,
     *     agents_inactive:int,
     *     agents_total:int,
     *     branches:int,
     *     other:int,
     *     activity_days:int
     * }
     */
    public function summaryCounts(?int $branchId = null): array
    {
        $activeIds = $this->activeAgentIds(7, $branchId);
        $inactiveIds = $this->inactiveAgentIds(7, $branchId);

        return [
            'admin' => User::query()->where('role', 'admin')->count(),
            'team_leaders' => User::query()->where('role', 'teamleader')->count(),
            'regional_managers' => User::query()->where('role', 'regional_manager')->count(),
            'agents_active' => $activeIds->count(),
            'agents_inactive' => $inactiveIds->count(),
            'agents_total' => $this->agentQuery($branchId)->count(),
            'branches' => Schema::hasTable('branches') ? Branch::query()->count() : 0,
            'other' => User::query()->whereNotIn('role', self::KNOWN_ROLES)->count(),
            'activity_days' => 7,
        ];
    }

    /**
     * Agents with at least one sale in the last N days.
     *
     * @return Collection<int, int>
     */
    public function activeAgentIds(int $days = 7, ?int $branchId = null): Collection
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $activeSellerIds = $this->stockInsights->agentIdsWithSalesSince($since);
        if ($activeSellerIds === []) {
            return collect();
        }

        return $this->agentQuery($branchId)
            ->whereIn('id', $activeSellerIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * Agents with no sale in the last N days.
     *
     * @return Collection<int, int>
     */
    public function inactiveAgentIds(int $days = 7, ?int $branchId = null): Collection
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $activeSellerIds = $this->stockInsights->agentIdsWithSalesSince($since);

        $query = $this->agentQuery($branchId);
        if ($activeSellerIds !== []) {
            $query->whereNotIn('id', $activeSellerIds);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values();
    }

    /**
     * @param  'active'|'inactive'  $filter
     * @return Collection<int, object>
     */
    public function agentsForActivityFilter(string $filter, int $days = 7, ?int $branchId = null): Collection
    {
        $agentIds = match ($filter) {
            'active' => $this->activeAgentIds($days, $branchId),
            'inactive' => $this->inactiveAgentIds($days, $branchId),
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

        $unsoldByAgent = $this->stockInsights->unsoldStockByAgent();
        $lastSaleByAgent = $this->stockInsights->lastSaleDatesForAgents($agentIds->all());

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

    private function agentQuery(?int $branchId): Builder
    {
        return User::query()
            ->where('role', 'agent')
            ->when(
                $branchId !== null && Schema::hasColumn('users', 'branch_id'),
                fn (Builder $q) => $q->where('branch_id', $branchId)
            );
    }
}
