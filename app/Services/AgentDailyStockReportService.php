<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgentDailyStockReportService
{
    /**
     * @return array{
     *   report_date: string,
     *   report_date_from: string,
     *   report_date_to: string,
     *   branch_id: int|null,
     *   agents: \Illuminate\Support\Collection,
     *   rows: array<int, array<string, mixed>>,
     *   totals: array<string, mixed>,
     * }
     */
    public function build(Carbon $dateFrom, ?Carbon $dateTo = null, ?int $branchId = null): array
    {
        $dateTo ??= $dateFrom->copy();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $rangeStart = $dateFrom->copy()->startOfDay();
        $rangeEnd = $dateTo->copy()->endOfDay();
        $prevEnd = $dateFrom->copy()->subDay()->endOfDay();
        $isSingleToday = $dateFrom->isSameDay($dateTo) && $dateFrom->isSameDay(Carbon::today());

        if (! Schema::hasTable('product_list')) {
            return [
                'report_date' => $dateFrom->toDateString(),
                'report_date_from' => $dateFrom->toDateString(),
                'report_date_to' => $dateTo->toDateString(),
                'branch_id' => $branchId,
                'agents' => collect(),
                'rows' => [],
                'totals' => $this->emptyTotals([]),
            ];
        }

        $productIds = $this->distinctProductIdsInScope($branchId);
        if ($productIds->isEmpty()) {
            $agents = $this->resolveAgentsForReport($branchId, [], [], []);

            return [
                'report_date' => $dateFrom->toDateString(),
                'report_date_from' => $dateFrom->toDateString(),
                'report_date_to' => $dateTo->toDateString(),
                'branch_id' => $branchId,
                'agents' => $agents,
                'rows' => [],
                'totals' => $this->emptyTotals($agents),
            ];
        }

        // Shop opening = closing at end of day before range start.
        $prevClosingShop = $this->closingShopByProduct($prevEnd, $branchId, $productIds);

        $salesShop = $this->salesShopByProduct($rangeStart, $rangeEnd, $branchId, $productIds);
        $salesAgents = $this->salesByAgentProduct($rangeStart, $rangeEnd, $branchId, $productIds);

        $unsoldAssignedAgents = $this->unsoldAssignedByAgentProduct($branchId, $productIds);
        $prevClosingAgents = $isSingleToday
            ? []
            : $this->closingByAgentProduct($prevEnd, $branchId, $productIds);

        $transferNetShop = $this->shopTransferNetByProductRange($rangeStart, $rangeEnd, $branchId, $productIds);
        $receivedInRange = $this->receivedInRangeByProduct($rangeStart, $rangeEnd, $branchId, $productIds);

        $agents = $this->resolveAgentsForReport($branchId, $salesAgents, $unsoldAssignedAgents, $prevClosingAgents);

        $activeProductIds = [];
        foreach ($productIds as $pid) {
            $pid = (int) $pid;
            $openingShop = (int) ($prevClosingShop[$pid] ?? 0);
            $sShop = (int) ($salesShop[$pid] ?? 0);
            $tShop = (int) ($transferNetShop[$pid] ?? 0);
            $recv = (int) ($receivedInRange[$pid] ?? 0);
            $closingShop = max(0, $openingShop - $sShop + $tShop + $recv);

            $rowHasActivity = $sShop > 0 || $tShop !== 0 || $recv > 0 || $openingShop > 0 || $closingShop > 0;
            foreach ($agents as $agent) {
                $aid = (int) $agent->id;
                $sA = (int) ($salesAgents[$aid][$pid] ?? 0);
                $openingA = $isSingleToday
                    ? (int) ($unsoldAssignedAgents[$aid][$pid] ?? 0) + $sA
                    : (int) ($prevClosingAgents[$aid][$pid] ?? 0);
                $closingA = max(0, $openingA - $sA);
                if ($openingA > 0 || $sA > 0 || $closingA > 0) {
                    $rowHasActivity = true;
                    break;
                }
            }
            if ($rowHasActivity) {
                $activeProductIds[] = $pid;
            }
        }

        $products = $activeProductIds === []
            ? collect()
            : Product::query()->whereIn('id', $activeProductIds)->orderBy('name')->get(['id', 'name']);

        $rows = [];
        foreach ($products as $product) {
            $pid = (int) $product->id;
            $openingShop = (int) ($prevClosingShop[$pid] ?? 0);
            $sShop = (int) ($salesShop[$pid] ?? 0);
            $tShop = (int) ($transferNetShop[$pid] ?? 0);
            $recv = (int) ($receivedInRange[$pid] ?? 0);
            // CLOSING: Opening − sales + transfers + units received (scanned) today
            $closingShop = max(0, $openingShop - $sShop + $tShop + $recv);

            $agentCells = [];
            foreach ($agents as $agent) {
                $aid = (int) $agent->id;
                $sA = (int) ($salesAgents[$aid][$pid] ?? 0);
                $openingA = $isSingleToday
                    ? (int) ($unsoldAssignedAgents[$aid][$pid] ?? 0) + $sA
                    : (int) ($prevClosingAgents[$aid][$pid] ?? 0);
                $closingA = max(0, $openingA - $sA);
                $agentCells[$aid] = [
                    'opening' => $openingA,
                    'sales' => $sA,
                    'closing' => $closingA,
                ];
            }

            $rows[] = [
                'product_id' => $pid,
                'name' => $product->name,
                'purchased_today' => $recv,
                'shop' => [
                    'opening' => $openingShop,
                    'sales' => $sShop,
                    'transfer' => $tShop,
                    'closing' => $closingShop,
                ],
                'agents' => $agentCells,
            ];
        }

        return [
            'report_date' => $dateFrom->toDateString(),
            'report_date_from' => $dateFrom->toDateString(),
            'report_date_to' => $dateTo->toDateString(),
            'branch_id' => $branchId,
            'agents' => $agents,
            'rows' => $rows,
            'totals' => $this->sumTotals($rows, $agents),
        ];
    }

    /**
     * Agents shown as columns: all active agents when no branch filter; when a branch is selected,
     * agents assigned to that branch plus any agent with stock/sales activity in branch-scoped data
     * (covers reps not yet assigned branch_id on their user row).
     *
     * @param  array<int, array<int, int>>  $salesByAgent
     * @param  array<int, array<int, int>>  $unsoldByAgent
     * @param  array<int, array<int, int>>  $prevClosingByAgent
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function resolveAgentsForReport(?int $branchId, array $salesByAgent, array $unsoldByAgent, array $prevClosingByAgent)
    {
        $base = User::query()
            ->where('role', 'agent')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            });

        if ($branchId === null || ! Schema::hasColumn('users', 'branch_id')) {
            return (clone $base)->with('branch')->orderBy('name')->get(['id', 'name', 'branch_id']);
        }

        $assignedIds = (clone $base)->where('branch_id', $branchId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $activityIds = array_unique(array_merge(
            array_keys($salesByAgent),
            array_keys($unsoldByAgent),
            array_keys($prevClosingByAgent)
        ));
        $merged = array_values(array_unique(array_merge($assignedIds, array_map('intval', $activityIds))));

        if ($merged === []) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $merged)
            ->where('role', 'agent')
            ->with('branch')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $agents
     */
    public function rowsToCsvLines(array $payload): array
    {
        $agents = $payload['agents'];
        $rows = $payload['rows'];
        $lines = [];

        $header = ['Product', 'Purchased in period', 'Total opening', 'Total sales', 'Total closing', 'Shop transfer'];
        foreach ($agents as $a) {
            $n = str_replace('"', '""', $a->name);
            $header[] = "{$n} opening";
            $header[] = "{$n} sales";
            $header[] = "{$n} closing";
        }
        $lines[] = $this->csvLine($header);

        foreach ($rows as $r) {
            $agentsCol = collect($r['agents'] ?? []);
            // Total columns = sum across agents only (shop / unassigned warehouse excluded).
            $totalO = (int) $agentsCol->sum('opening');
            $totalS = (int) $agentsCol->sum('sales');
            $totalC = (int) $agentsCol->sum('closing');
            $line = [
                $r['name'],
                (string) $r['purchased_today'],
                (string) $totalO,
                (string) $totalS,
                (string) $totalC,
                (string) ($r['shop']['transfer'] ?? 0),
            ];
            foreach ($agents as $a) {
                $c = $r['agents'][(int) $a->id] ?? ['opening' => 0, 'sales' => 0, 'closing' => 0];
                $line[] = (string) $c['opening'];
                $line[] = (string) $c['sales'];
                $line[] = (string) $c['closing'];
            }
            $lines[] = $this->csvLine($line);
        }

        $t = $payload['totals'];
        $totAgents = collect($t['agents'] ?? []);
        $grandO = (int) $totAgents->sum('opening');
        $grandS = (int) $totAgents->sum('sales');
        $grandC = (int) $totAgents->sum('closing');
        $tot = ['Total', (string) $t['purchased_today'], (string) $grandO, (string) $grandS, (string) $grandC, (string) ($t['shop']['transfer'] ?? 0)];
        foreach ($agents as $a) {
            $c = $t['agents'][(int) $a->id] ?? ['opening' => 0, 'sales' => 0, 'closing' => 0];
            $tot[] = (string) $c['opening'];
            $tot[] = (string) $c['sales'];
            $tot[] = (string) $c['closing'];
        }
        $lines[] = $this->csvLine($tot);

        return $lines;
    }

    private function csvLine(array $cells): string
    {
        $escaped = array_map(function ($v) {
            $s = (string) $v;
            if (str_contains($s, '"') || str_contains($s, ',') || str_contains($s, "\n") || str_contains($s, "\r")) {
                return '"'.str_replace('"', '""', $s).'"';
            }

            return $s;
        }, $cells);

        return implode(',', $escaped);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $agents
     */
    private function emptyTotals($agents): array
    {
        $a = [];
        foreach ($agents as $agent) {
            $a[(int) $agent->id] = ['opening' => 0, 'sales' => 0, 'closing' => 0];
        }

        return [
            'purchased_today' => 0,
            'shop' => ['opening' => 0, 'sales' => 0, 'transfer' => 0, 'closing' => 0],
            'agents' => $a,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  \Illuminate\Support\Collection<int, User>  $agents
     */
    private function sumTotals(array $rows, $agents): array
    {
        $tot = $this->emptyTotals($agents);
        foreach ($rows as $r) {
            $tot['purchased_today'] += $r['purchased_today'];
            foreach (['opening', 'sales', 'transfer', 'closing'] as $k) {
                $tot['shop'][$k] += $r['shop'][$k];
            }
            foreach ($agents as $agent) {
                $aid = (int) $agent->id;
                $c = $r['agents'][$aid] ?? ['opening' => 0, 'sales' => 0, 'closing' => 0];
                $tot['agents'][$aid]['opening'] += $c['opening'];
                $tot['agents'][$aid]['sales'] += $c['sales'];
                $tot['agents'][$aid]['closing'] += $c['closing'];
            }
        }

        return $tot;
    }

    private function baseListQuery(?int $branchId)
    {
        $q = ProductListItem::query()->whereNotNull('product_id');
        if ($branchId !== null) {
            $q->whereEffectiveBranch($branchId);
        }

        return $q;
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function distinctProductIdsInScope(?int $branchId)
    {
        return $this->baseListQuery($branchId)->distinct()->pluck('product_id')->filter()->values();
    }

    /**
     * Closing inventory at end of $at: unsold OR sold after $at.
     *
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $productIds
     * @return array<int, int> product_id => count
     */
    private function closingShopByProduct(Carbon $at, ?int $branchId, $productIds): array
    {
        if ($productIds->isEmpty()) {
            return [];
        }

        $ids = $productIds->all();
        $q = $this->baseListQuery($branchId)
            ->whereIn('product_id', $ids)
            ->whereDoesntHave('agentProductListAssignment')
            ->where(function ($q2) use ($at) {
                $q2->whereNull('sold_at')
                    ->orWhere('sold_at', '>', $at);
            })
            ->selectRaw('product_id, COUNT(*) as c')
            ->groupBy('product_id');

        return $this->mapCounts($q->get());
    }

    /**
     * IMEIs currently assigned to an agent and not sold (sold_at IS NULL).
     * Used with same-day sales to derive a stable per-day opening: unsold + sales(today) = opening at day start.
     *
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $productIds
     * @return array<int, array<int, int>> agent_id => [product_id => count]
     */
    private function unsoldAssignedByAgentProduct(?int $branchId, $productIds): array
    {
        if ($productIds->isEmpty()) {
            return [];
        }

        $ids = $productIds->all();
        $rows = DB::table('product_list as pl')
            ->join('agent_product_list_assignments as a', 'a.product_list_id', '=', 'pl.id')
            ->whereIn('pl.product_id', $ids)
            ->whereNull('pl.sold_at')
            ->when($branchId !== null, function ($q) use ($branchId) {
                $q->where(function ($w) use ($branchId) {
                    $w->where('pl.branch_id', $branchId)
                        ->orWhere(function ($inner) use ($branchId) {
                            $inner->whereNull('pl.branch_id')
                                ->whereExists(function ($sub) use ($branchId) {
                                    $sub->selectRaw('1')
                                        ->from('purchases as pu')
                                        ->whereColumn('pu.id', 'pl.purchase_id')
                                        ->where('pu.branch_id', $branchId);
                                });
                        });
                });
            })
            ->groupBy('a.agent_id', 'pl.product_id')
            ->selectRaw('a.agent_id as agent_id, pl.product_id as product_id, COUNT(*) as c')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $aid = (int) $r->agent_id;
            $pid = (int) $r->product_id;
            $out[$aid][$pid] = (int) $r->c;
        }

        return $out;
    }

    /**
     * Agent closing inventory at instant $at (end of previous day): assignment join with
     * sold_at null OR sold after $at. Used for **past** report dates only (see build()).
     *
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $productIds
     * @return array<int, array<int, int>> agent_id => [product_id => count]
     */
    private function closingByAgentProduct(Carbon $at, ?int $branchId, $productIds): array
    {
        if ($productIds->isEmpty()) {
            return [];
        }

        $ids = $productIds->all();
        $rows = DB::table('product_list as pl')
            ->join('agent_product_list_assignments as a', 'a.product_list_id', '=', 'pl.id')
            ->whereIn('pl.product_id', $ids)
            ->where(function ($q2) use ($at) {
                $q2->whereNull('pl.sold_at')
                    ->orWhere('pl.sold_at', '>', $at);
            })
            ->when($branchId !== null, function ($q) use ($branchId) {
                $q->where(function ($w) use ($branchId) {
                    $w->where('pl.branch_id', $branchId)
                        ->orWhere(function ($inner) use ($branchId) {
                            $inner->whereNull('pl.branch_id')
                                ->whereExists(function ($sub) use ($branchId) {
                                    $sub->selectRaw('1')
                                        ->from('purchases as pu')
                                        ->whereColumn('pu.id', 'pl.purchase_id')
                                        ->where('pu.branch_id', $branchId);
                                });
                        });
                });
            })
            ->groupBy('a.agent_id', 'pl.product_id')
            ->selectRaw('a.agent_id as agent_id, pl.product_id as product_id, COUNT(*) as c')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $aid = (int) $r->agent_id;
            $pid = (int) $r->product_id;
            $out[$aid][$pid] = (int) $r->c;
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $productIds
     * @return array<int, int>
     */
    private function salesShopByProduct(Carbon $dayStart, Carbon $dayEnd, ?int $branchId, $productIds): array
    {
        if ($productIds->isEmpty()) {
            return [];
        }

        // Shop = sold from stock not attributed to an agent. Agent sales clear the assignment row, so we
        // must exclude rows linked to agent_sales (with agent), pending_sales (with seller_id), or agent_credits.
        $q = $this->baseListQuery($branchId)
            ->whereIn('product_id', $productIds->all())
            ->whereBetween('sold_at', [$dayStart, $dayEnd])
            ->where(function ($w) {
                $w->whereDoesntHave('agentSale', fn ($a) => $a->whereNotNull('agent_id'))
                    ->whereDoesntHave('pendingSale', fn ($p) => $p->whereNotNull('seller_id'));
            });
        if (Schema::hasColumn('product_list', 'agent_credit_id')) {
            $q->whereNull('agent_credit_id');
        }
        $q->selectRaw('product_id, COUNT(*) as c')
            ->groupBy('product_id');

        return $this->mapCounts($q->get());
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $productIds
     * @return array<int, array<int, int>>
     */
    private function salesByAgentProduct(Carbon $dayStart, Carbon $dayEnd, ?int $branchId, $productIds): array
    {
        if ($productIds->isEmpty()) {
            return [];
        }

        $pids = $productIds->all();
        $applyBranchOnPl = $this->branchFilterOnProductListSql($branchId);

        $out = [];

        $accumulate = function ($rows) use (&$out) {
            foreach ($rows as $r) {
                $aid = (int) $r->agent_id;
                $pid = (int) $r->product_id;
                $out[$aid][$pid] = ($out[$aid][$pid] ?? 0) + (int) $r->c;
            }
        };

        // Finalised agent sales (assignment row is removed on sell).
        $accumulate(
            DB::table('product_list as pl')
                ->join('agent_sales as ag', 'ag.id', '=', 'pl.agent_sale_id')
                ->whereIn('pl.product_id', $pids)
                ->whereNotNull('ag.agent_id')
                ->whereBetween('pl.sold_at', [$dayStart, $dayEnd])
                ->when($branchId !== null, $applyBranchOnPl)
                ->groupBy('ag.agent_id', 'pl.product_id')
                ->selectRaw('ag.agent_id as agent_id, pl.product_id as product_id, COUNT(*) as c')
                ->get()
        );

        // Cash/card flow: pending sale with seller until converted to agent_sales.
        if (Schema::hasColumn('pending_sales', 'seller_id')) {
            $accumulate(
                DB::table('product_list as pl')
                    ->join('pending_sales as ps', 'ps.id', '=', 'pl.pending_sale_id')
                    ->whereIn('pl.product_id', $pids)
                    ->whereNotNull('ps.seller_id')
                    ->whereBetween('pl.sold_at', [$dayStart, $dayEnd])
                    ->when($branchId !== null, $applyBranchOnPl)
                    ->groupBy('ps.seller_id', 'pl.product_id')
                    ->selectRaw('ps.seller_id as agent_id, pl.product_id as product_id, COUNT(*) as c')
                    ->get()
            );
        }

        // Credit sale path (assignment removed on sell).
        if (Schema::hasTable('agent_credits') && Schema::hasColumn('product_list', 'agent_credit_id')) {
            $accumulate(
                DB::table('product_list as pl')
                    ->join('agent_credits as ac', 'ac.id', '=', 'pl.agent_credit_id')
                    ->whereIn('pl.product_id', $pids)
                    ->whereBetween('pl.sold_at', [$dayStart, $dayEnd])
                    ->when($branchId !== null, $applyBranchOnPl)
                    ->groupBy('ac.agent_id', 'pl.product_id')
                    ->selectRaw('ac.agent_id as agent_id, pl.product_id as product_id, COUNT(*) as c')
                    ->get()
            );
        }

        // Legacy rows that still have an assignment after sale (should be rare). Skip if already counted above.
        $legacyAssign = DB::table('product_list as pl')
            ->join('agent_product_list_assignments as a', 'a.product_list_id', '=', 'pl.id')
            ->whereIn('pl.product_id', $pids)
            ->whereBetween('pl.sold_at', [$dayStart, $dayEnd])
            ->whereNull('pl.agent_sale_id')
            ->whereNull('pl.pending_sale_id');
        if (Schema::hasColumn('product_list', 'agent_credit_id')) {
            $legacyAssign->whereNull('pl.agent_credit_id');
        }
        $accumulate(
            $legacyAssign
                ->when($branchId !== null, $applyBranchOnPl)
                ->groupBy('a.agent_id', 'pl.product_id')
                ->selectRaw('a.agent_id as agent_id, pl.product_id as product_id, COUNT(*) as c')
                ->get()
        );

        return $out;
    }

    /**
     * @return \Closure(\Illuminate\Database\Query\Builder): void
     */
    private function branchFilterOnProductListSql(?int $branchId): \Closure
    {
        return function ($q) use ($branchId) {
            if ($branchId === null) {
                return;
            }
            $q->where(function ($w) use ($branchId) {
                $w->where('pl.branch_id', $branchId)
                    ->orWhere(function ($inner) use ($branchId) {
                        $inner->whereNull('pl.branch_id')
                            ->whereExists(function ($sub) use ($branchId) {
                                $sub->selectRaw('1')
                                    ->from('purchases as pu')
                                    ->whereColumn('pu.id', 'pl.purchase_id')
                                    ->where('pu.branch_id', $branchId);
                            });
                    });
            });
        };
    }

    /**
     * Net branch transfers for shop (unassigned) stock: transfers in − transfers out for the day.
     * When $branchId is set, in = to_branch_id, out = from_branch_id for that branch.
     * When null, net = all to_* minus all from_* counts per product (approximate global movement).
     *
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $productIds
     * @return array<int, int>
     */
    private function shopTransferNetByProduct(Carbon $reportDate, ?int $branchId, $productIds): array
    {
        if ($productIds->isEmpty() || ! Schema::hasTable('branch_transfer_logs')) {
            return [];
        }

        $day = $reportDate->toDateString();
        $pids = $productIds->all();

        $applyBranchOnPl = function ($q) use ($branchId) {
            if ($branchId === null) {
                return;
            }
            $q->where(function ($w) use ($branchId) {
                $w->where('pl.branch_id', $branchId)
                    ->orWhere(function ($inner) use ($branchId) {
                        $inner->whereNull('pl.branch_id')
                            ->whereExists(function ($sub) use ($branchId) {
                                $sub->selectRaw('1')
                                    ->from('purchases as pu')
                                    ->whereColumn('pu.id', 'pl.purchase_id')
                                    ->where('pu.branch_id', $branchId);
                            });
                    });
            });
        };

        $makeBase = function () use ($day, $pids, $applyBranchOnPl) {
            $q = DB::table('branch_transfer_logs as bt')
                ->join('product_list as pl', 'pl.id', '=', 'bt.product_list_id')
                ->whereDate('bt.created_at', $day)
                ->whereIn('pl.product_id', $pids)
                ->whereNotExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('agent_product_list_assignments as ap')
                        ->whereColumn('ap.product_list_id', 'pl.id');
                });
            $applyBranchOnPl($q);

            return $q;
        };

        $inRows = $makeBase()
            ->when($branchId !== null, fn ($q) => $q->where('bt.to_branch_id', $branchId))
            ->whereNotNull('bt.to_branch_id')
            ->groupBy('pl.product_id')
            ->selectRaw('pl.product_id as product_id, COUNT(*) as c')
            ->get();

        $out = [];
        foreach ($inRows as $r) {
            $out[(int) $r->product_id] = (int) $r->c;
        }

        $outRows = $makeBase()
            ->when($branchId !== null, fn ($q) => $q->where('bt.from_branch_id', $branchId))
            ->whereNotNull('bt.from_branch_id')
            ->groupBy('pl.product_id')
            ->selectRaw('pl.product_id as product_id, COUNT(*) as c')
            ->get();

        foreach ($outRows as $r) {
            $pid = (int) $r->product_id;
            $out[$pid] = ($out[$pid] ?? 0) - (int) $r->c;
        }

        return $out;
    }

    /**
     * New product_list rows created this calendar day (received / scanned in).
     *
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $productIds
     * @return array<int, int>
     */
    private function receivedTodayByProduct(Carbon $reportDate, ?int $branchId, $productIds): array
    {
        return $this->receivedInRangeByProduct(
            $reportDate->copy()->startOfDay(),
            $reportDate->copy()->endOfDay(),
            $branchId,
            $productIds
        );
    }

    /**
     * New product_list rows created within the date range (received / scanned in).
     *
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $productIds
     * @return array<int, int>
     */
    private function receivedInRangeByProduct(Carbon $rangeStart, Carbon $rangeEnd, ?int $branchId, $productIds): array
    {
        if ($productIds->isEmpty()) {
            return [];
        }

        $q = $this->baseListQuery($branchId)
            ->whereIn('product_id', $productIds->all())
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->selectRaw('product_id, COUNT(*) as c')
            ->groupBy('product_id');

        return $this->mapCounts($q->get());
    }

    /**
     * Net branch transfers for shop stock across a date range.
     *
     * @param  \Illuminate\Support\Collection<int, int>|array<int>  $productIds
     * @return array<int, int>
     */
    private function shopTransferNetByProductRange(Carbon $rangeStart, Carbon $rangeEnd, ?int $branchId, $productIds): array
    {
        if ($productIds->isEmpty() || ! Schema::hasTable('branch_transfer_logs')) {
            return [];
        }

        $pids = $productIds->all();

        $applyBranchOnPl = function ($q) use ($branchId) {
            if ($branchId === null) {
                return;
            }
            $q->where(function ($w) use ($branchId) {
                $w->where('pl.branch_id', $branchId)
                    ->orWhere(function ($inner) use ($branchId) {
                        $inner->whereNull('pl.branch_id')
                            ->whereExists(function ($sub) use ($branchId) {
                                $sub->selectRaw('1')
                                    ->from('purchases as pu')
                                    ->whereColumn('pu.id', 'pl.purchase_id')
                                    ->where('pu.branch_id', $branchId);
                            });
                    });
            });
        };

        $makeBase = function () use ($rangeStart, $rangeEnd, $pids, $applyBranchOnPl) {
            $q = DB::table('branch_transfer_logs as bt')
                ->join('product_list as pl', 'pl.id', '=', 'bt.product_list_id')
                ->whereBetween('bt.created_at', [$rangeStart, $rangeEnd])
                ->whereIn('pl.product_id', $pids)
                ->whereNotExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('agent_product_list_assignments as ap')
                        ->whereColumn('ap.product_list_id', 'pl.id');
                });
            $applyBranchOnPl($q);

            return $q;
        };

        $inRows = $makeBase()
            ->when($branchId !== null, fn ($q) => $q->where('bt.to_branch_id', $branchId))
            ->whereNotNull('bt.to_branch_id')
            ->groupBy('pl.product_id')
            ->selectRaw('pl.product_id as product_id, COUNT(*) as c')
            ->get();

        $out = [];
        foreach ($inRows as $r) {
            $out[(int) $r->product_id] = (int) $r->c;
        }

        $outRows = $makeBase()
            ->when($branchId !== null, fn ($q) => $q->where('bt.from_branch_id', $branchId))
            ->whereNotNull('bt.from_branch_id')
            ->groupBy('pl.product_id')
            ->selectRaw('pl.product_id as product_id, COUNT(*) as c')
            ->get();

        foreach ($outRows as $r) {
            $pid = (int) $r->product_id;
            $out[$pid] = ($out[$pid] ?? 0) - (int) $r->c;
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $rows
     * @return array<int, int>
     */
    private function mapCounts($rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->product_id] = (int) $r->c;
        }

        return $out;
    }

    /**
     * @param  array<int>  $productIds
     * @return array<int, float> product_id => unit price from most recent purchase row
     */
    private function latestPurchaseUnitPricesByProduct(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $out = [];
        $rows = Purchase::query()
            ->whereIn('product_id', $productIds)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get(['product_id', 'sell_price', 'unit_price']);

        foreach ($rows as $row) {
            $pid = (int) $row->product_id;
            if (isset($out[$pid])) {
                continue;
            }
            $sell = $row->sell_price;
            $unit = $row->unit_price;
            $v = (float) ($sell !== null && (float) $sell > 0 ? $sell : ($unit ?? 0));
            if ($v > 0.00001) {
                $out[$pid] = $v;
            }
        }

        return $out;
    }

    private function displayUnitPriceTzs(Product $product, ?float $purchaseFallback): float
    {
        $catalog = (float) ($product->price ?? 0);
        if ($catalog > 0.00001) {
            return $catalog;
        }
        if ($purchaseFallback !== null && $purchaseFallback > 0.00001) {
            return $purchaseFallback;
        }

        return 0.0;
    }
}
