<?php

namespace App\Services;

use App\Models\AgentAssignment;
use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\DistributionSale;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardFinancialService
{
    public function __construct(
        protected DistributionSaleService $distributionSaleService
    ) {}

    /**
     * Total pending (not paid) from purchases.
     */
    public function payables(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $total = 0;
        $query = Purchase::query();
        $this->applyDateRange($query, 'date', $startDate, $endDate);
        foreach ($query->get() as $purchase) {
            $totalAmount = $purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price);
            $total += max(0, $totalAmount - ($purchase->paid_amount ?? 0));
        }
        return (float) $total;
    }

    /**
     * Total pending (not collected) from Distribution Sales and Agent Credits.
     */
    public function receivables(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        return $this->distributionReceivables($startDate, $endDate) + $this->agentCreditReceivables($startDate, $endDate);
    }

    /**
     * Total pending from Distribution Sales (balance).
     */
    public function distributionReceivables(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = DistributionSale::query();
        $this->applyDateRange($query, 'date', $startDate, $endDate);

        return (float) $query->get()->sum(fn ($s) => (float) ($s->balance ?? max(0, ($s->total_selling_value ?? 0) - ($s->paid_amount ?? 0))));
    }

    /**
     * Total pending from Agent Credits (credit amount not yet paid).
     */
    public function agentCreditReceivables(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = AgentCredit::query();
        $this->applyDateRange($query, 'date', $startDate, $endDate);

        return (float) $query->get()->sum(function (AgentCredit $credit) {
            $total = (float) ($credit->total_amount ?? 0);
            $paid = (float) ($credit->paid_amount ?? 0);

            return max(0, $total - $paid);
        });
    }

    /**
     * Per-dealer (distributor) totals: billed, collected, outstanding — for dashboard receivables detail.
     *
     * @return list<array{dealer_name: string, dealer_id: int|null, total_billed: float, total_paid: float, outstanding: float, aging_days: int|null, aging_label: string|null}>
     */
    public function getDistributorReceivableBreakdown(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = DistributionSale::query()
            ->with(['dealer:id,name']);
        $this->applyDateRange($query, 'date', $startDate, $endDate);
        $sales = $query->get(['id', 'dealer_id', 'dealer_name', 'date', 'total_selling_value', 'paid_amount', 'balance']);

        return $sales
            ->groupBy(function (DistributionSale $s) {
                if ($s->dealer_id) {
                    return 'id:' . $s->dealer_id;
                }

                return 'name:' . md5(strtolower(trim((string) ($s->dealer_name ?? ''))));
            })
            ->map(function ($group) {
                /** @var \Illuminate\Support\Collection<int, DistributionSale> $group */
                $first = $group->first();
                $name = $first->dealer?->name
                    ?? (trim((string) ($first->dealer_name ?? '')) !== '' ? $first->dealer_name : 'Unknown dealer');

                $totalBilled = (float) $group->sum(fn (DistributionSale $s) => (float) ($s->total_selling_value ?? 0));
                $totalPaid = (float) $group->sum(fn (DistributionSale $s) => (float) ($s->paid_amount ?? 0));
                $outstanding = (float) $group->sum(function (DistributionSale $s) {
                    if ($s->balance !== null) {
                        return (float) $s->balance;
                    }
                    $t = (float) ($s->total_selling_value ?? 0);
                    $p = (float) ($s->paid_amount ?? 0);

                    return max(0, $t - $p);
                });

                $eps = 0.0001;
                $agingDays = null;
                if ($outstanding > $eps) {
                    $oldestOutstandingDate = $group
                        ->filter(function (DistributionSale $s) use ($eps) {
                            $remaining = $s->balance !== null
                                ? (float) $s->balance
                                : max(0, (float) ($s->total_selling_value ?? 0) - (float) ($s->paid_amount ?? 0));

                            return $remaining > $eps;
                        })
                        ->map(function (DistributionSale $s) {
                            $d = $s->date;
                            if ($d === null) {
                                return null;
                            }

                            return $d instanceof Carbon ? $d->copy()->startOfDay() : Carbon::parse($d)->startOfDay();
                        })
                        ->filter()
                        ->sort()
                        ->first();

                    if ($oldestOutstandingDate) {
                        $agingDays = max(0, (int) floor($oldestOutstandingDate->floatDiffInRealDays(Carbon::now()->startOfDay())));
                    }
                }

                $agingLabel = $agingDays === null ? null : $this->formatAgingLabel($agingDays);

                return [
                    'dealer_name' => $name,
                    'dealer_id' => $first->dealer_id,
                    'total_billed' => $totalBilled,
                    'total_paid' => $totalPaid,
                    'outstanding' => $outstanding,
                    'aging_days' => $agingDays,
                    'aging_label' => $agingLabel,
                ];
            })
            ->values()
            ->sort(function (array $a, array $b): int {
                $aAging = $a['aging_days'];
                $bAging = $b['aging_days'];
                if ($aAging !== null || $bAging !== null) {
                    if ($aAging === null) {
                        return 1;
                    }
                    if ($bAging === null) {
                        return -1;
                    }
                    $byAging = $bAging <=> $aAging;
                    if ($byAging !== 0) {
                        return $byAging;
                    }
                }

                return ($b['outstanding'] <=> $a['outstanding']);
            })
            ->values()
            ->all();
    }

    private function formatAgingLabel(int $diffDays): string
    {
        if ($diffDays < 7) {
            return $diffDays . ' day' . ($diffDays === 1 ? '' : 's') . '+';
        }
        if ($diffDays < 30) {
            $weeks = (int) floor($diffDays / 7);

            return $weeks . ' week' . ($weeks === 1 ? '' : 's') . '+';
        }
        $months = (int) floor($diffDays / 30);

        return $months . ' month' . ($months === 1 ? '' : 's') . '+';
    }

    /**
     * Agent credit receivables summary for dashboard.
     *
     * @return array{credits: int, total_credit: float, total_paid: float, outstanding: float}
     */
    public function getAgentCreditReceivableSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = AgentCredit::query();
        $this->applyDateRange($query, 'date', $startDate, $endDate);
        $credits = $query->get(['total_amount', 'paid_amount']);
        $totalCredit = (float) $credits->sum(fn (AgentCredit $credit) => (float) ($credit->total_amount ?? 0));
        $totalPaid = (float) $credits->sum(fn (AgentCredit $credit) => (float) ($credit->paid_amount ?? 0));

        return [
            'credits' => $credits->count(),
            'total_credit' => $totalCredit,
            'total_paid' => $totalPaid,
            'outstanding' => max(0, $totalCredit - $totalPaid),
        ];
    }

    /**
     * Total value of our stock (products.stock_quantity * cost per unit).
     */
    public function stockInHandValue(): float
    {
        $total = 0;
        foreach (Product::all() as $product) {
            $buyPrice = $this->distributionSaleService->getBuyPriceForProduct($product->id);
            $qty = (int) ($product->stock_quantity ?? 0);
            $total += $buyPrice * $qty;
        }
        return (float) $total;
    }

    /**
     * Total value of stocks given to agents (with agents, not yet sold).
     */
    public function cashInHand(): float
    {
        $total = 0;
        $assignments = AgentAssignment::with('product')->get();
        foreach ($assignments as $assignment) {
            $remaining = max(0, ($assignment->quantity_assigned ?? 0) - ($assignment->quantity_sold ?? 0));
            if ($remaining > 0 && $assignment->product_id) {
                $buyPrice = $this->distributionSaleService->getBuyPriceForProduct($assignment->product_id);
                $total += $buyPrice * $remaining;
            }
        }
        return (float) $total;
    }

    /**
     * Sum of receivables, stock in hand value, and cash in hand.
     */
    public function totalValue(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        return $this->receivables($startDate, $endDate) + $this->stockInHandValue() + $this->cashInHand();
    }

    /**
     * Profit from Distribution Sales + Agent Sales + Agent Credits (per-credit: financed amount minus current buy price for the product).
     */
    public function grossProfit(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $distributionQuery = DistributionSale::query();
        $this->applyDateRange($distributionQuery, 'date', $startDate, $endDate);
        $distProfit = (float) $distributionQuery->sum('profit');

        $agentSalesQuery = AgentSale::query();
        $this->applyDateRange($agentSalesQuery, 'date', $startDate, $endDate);
        $agentProfit = (float) $agentSalesQuery->sum('profit');

        return $distProfit + $agentProfit + $this->agentCreditsProfit($startDate, $endDate);
    }

    /**
     * Margin on agent credit sales: same basis as agent cash sales (sell amount − latest purchase unit price per product).
     */
    private function agentCreditsProfit(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = AgentCredit::query()->whereNotNull('product_id');
        $this->applyDateRange($query, 'date', $startDate, $endDate);
        $credits = $query->get(['product_id', 'total_amount']);
        if ($credits->isEmpty()) {
            return 0.0;
        }

        $buyByProductId = [];
        $total = 0.0;
        foreach ($credits as $credit) {
            $productId = (int) $credit->product_id;
            if (! isset($buyByProductId[$productId])) {
                $buyByProductId[$productId] = $this->distributionSaleService->getBuyPriceForProduct($productId);
            }
            $total += (float) ($credit->total_amount ?? 0) - $buyByProductId[$productId];
        }

        return (float) $total;
    }

    /**
     * Total from Expenses section (admin expenses).
     */
    public function totalExpenses(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = Expense::query();
        $this->applyDateRange($query, 'date', $startDate, $endDate);

        return (float) $query->sum('amount');
    }

    /**
     * Gross profit - Total expenses.
     */
    public function netProfit(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        return $this->grossProfit($startDate, $endDate) - $this->totalExpenses($startDate, $endDate);
    }

    /**
     * Get all financial metrics as an array.
     */
    public function getMetrics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        return [
            'payables' => $this->payables($startDate, $endDate),
            'receivables' => $this->receivables($startDate, $endDate),
            'stock_in_hand_value' => $this->stockInHandValue(),
            'cash_in_hand' => $this->cashInHand(),
            'total_value' => $this->totalValue($startDate, $endDate),
            'gross_profit' => $this->grossProfit($startDate, $endDate),
            'total_expenses' => $this->totalExpenses($startDate, $endDate),
            'net_profit' => $this->netProfit($startDate, $endDate),
            'total_purchase_buy_price' => $this->totalPurchaseBuyPrice(),
            'total_products_in_purchases' => $this->totalProductsInPurchases(),
        ];
    }

    private function applyDateRange($query, string $column, ?Carbon $startDate, ?Carbon $endDate): void
    {
        if (! $startDate && ! $endDate) {
            return;
        }

        $start = $startDate ? $startDate->copy()->startOfDay() : Carbon::minValue();
        $end = $endDate ? $endDate->copy()->endOfDay() : Carbon::maxValue();

        $query->whereBetween($column, [$start, $end]);
    }

    /**
     * Calculate total sales from Orders, DistributionSales, AgentSales, and AgentCredits for a date range.
     */
    private function calculateSalesForPeriod(Carbon $startDate, Carbon $endDate): float
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        // Orders: use created_at
        $ordersSales = Order::whereBetween('created_at', [$start, $end])
            ->sum('total_price');

        // DistributionSales: use date field
        $distributionSales = DistributionSale::whereBetween('date', [$start, $end])
            ->sum('total_selling_value');

        // AgentSales: use date field
        $agentSales = AgentSale::whereBetween('date', [$start, $end])
            ->sum('total_selling_value');

        // AgentCredits: count financed sales value in the same period.
        $agentCredits = AgentCredit::whereBetween('date', [$start, $end])
            ->sum('total_amount');

        return (float) ($ordersSales + $distributionSales + $agentSales + $agentCredits);
    }

    /**
     * Calculate percentage change between two values.
     */
    private function calculatePercentageChange(float $current, float $previous): ?float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : null;
        }
        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Get today's sales vs yesterday.
     */
    public function getTodaySales(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todaySales = $this->calculateSalesForPeriod($today, $today);
        $yesterdaySales = $this->calculateSalesForPeriod($yesterday, $yesterday);
        $percentageChange = $this->calculatePercentageChange($todaySales, $yesterdaySales);

        return [
            'sales' => $todaySales,
            'previous_sales' => $yesterdaySales,
            'percentage_change' => $percentageChange,
            'is_increase' => $percentageChange !== null && $percentageChange >= 0,
        ];
    }

    /**
     * Get Weekly To Date sales vs previous week same period.
     */
    public function getWTDSales(): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy();

        // Previous week same period: from start of previous week to same day of previous week
        $previousWeekStart = $now->copy()->subWeek()->startOfWeek();
        $previousWeekEnd = $now->copy()->subWeek();

        $wtdSales = $this->calculateSalesForPeriod($startOfWeek, $endOfWeek);
        $previousWeekSales = $this->calculateSalesForPeriod($previousWeekStart, $previousWeekEnd);
        $percentageChange = $this->calculatePercentageChange($wtdSales, $previousWeekSales);

        return [
            'sales' => $wtdSales,
            'previous_sales' => $previousWeekSales,
            'percentage_change' => $percentageChange,
            'is_increase' => $percentageChange !== null && $percentageChange >= 0,
        ];
    }

    /**
     * Get Monthly To Date sales vs previous month same period.
     */
    public function getMTDSales(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy();

        // Previous month same period
        $previousMonthStart = $startOfMonth->copy()->subMonth();
        $previousMonthEnd = $endOfMonth->copy()->subMonth();

        $mtdSales = $this->calculateSalesForPeriod($startOfMonth, $endOfMonth);
        $previousMonthSales = $this->calculateSalesForPeriod($previousMonthStart, $previousMonthEnd);
        $percentageChange = $this->calculatePercentageChange($mtdSales, $previousMonthSales);

        return [
            'sales' => $mtdSales,
            'previous_sales' => $previousMonthSales,
            'percentage_change' => $percentageChange,
            'is_increase' => $percentageChange !== null && $percentageChange >= 0,
        ];
    }

    /**
     * Get Yearly To Date sales vs previous year same period.
     */
    public function getYTDSales(): array
    {
        $now = Carbon::now();
        $startOfYear = $now->copy()->startOfYear();
        $endOfYear = $now->copy();

        // Previous year same period
        $previousYearStart = $startOfYear->copy()->subYear();
        $previousYearEnd = $endOfYear->copy()->subYear();

        $ytdSales = $this->calculateSalesForPeriod($startOfYear, $endOfYear);
        $previousYearSales = $this->calculateSalesForPeriod($previousYearStart, $previousYearEnd);
        $percentageChange = $this->calculatePercentageChange($ytdSales, $previousYearSales);

        return [
            'sales' => $ytdSales,
            'previous_sales' => $previousYearSales,
            'percentage_change' => $percentageChange,
            'is_increase' => $percentageChange !== null && $percentageChange >= 0,
        ];
    }

    /**
     * Get all sales metrics.
     */
    public function getSalesMetrics(): array
    {
        return [
            'today' => $this->getTodaySales(),
            'wtd' => $this->getWTDSales(),
            'mtd' => $this->getMTDSales(),
            'ytd' => $this->getYTDSales(),
        ];
    }

    /**
     * Get top selling products (models) by quantity sold within a date range.
     */
    public function getTopSellingProducts(?Carbon $startDate = null, ?Carbon $endDate = null, int $limit = 10): array
    {
        $start = $startDate ? $startDate->copy()->startOfDay() : Carbon::now()->subMonths(1)->startOfDay();
        $end = $endDate ? $endDate->copy()->endOfDay() : Carbon::now()->endOfDay();
        $productTable = (new Product())->getTable();

        // Avoid hard crashes on login/dashboard when some tables are missing.
        if (! Schema::hasTable($productTable)) {
            return [];
        }

        // Get sales from Orders (via OrderItems)
        $orderSales = collect();
        if (Schema::hasTable('order_items') && Schema::hasTable('orders')) {
            $orderSales = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join($productTable, 'order_items.product_id', '=', $productTable . '.id')
                ->whereBetween('orders.created_at', [$start, $end])
                ->select(
                    $productTable . '.id',
                    $productTable . '.name as model',
                    DB::raw('CAST(SUM(order_items.quantity) AS UNSIGNED) as total_quantity')
                )
                ->groupBy($productTable . '.id', $productTable . '.name')
                ->get();
        }

        // Get sales from DistributionSales
        $distributionSales = collect();
        if (Schema::hasTable('distribution_sales')) {
            $distributionSales = DB::table('distribution_sales')
                ->join($productTable, 'distribution_sales.product_id', '=', $productTable . '.id')
                ->whereBetween('distribution_sales.date', [$start, $end])
                ->select(
                    $productTable . '.id',
                    $productTable . '.name as model',
                    DB::raw('CAST(SUM(distribution_sales.quantity_sold) AS UNSIGNED) as total_quantity')
                )
                ->groupBy($productTable . '.id', $productTable . '.name')
                ->get();
        }

        // Get sales from AgentSales
        $agentSales = collect();
        if (Schema::hasTable('agent_sales')) {
            $agentSales = DB::table('agent_sales')
                ->join($productTable, 'agent_sales.product_id', '=', $productTable . '.id')
                ->whereBetween('agent_sales.date', [$start, $end])
                ->select(
                    $productTable . '.id',
                    $productTable . '.name as model',
                    DB::raw('CAST(SUM(agent_sales.quantity_sold) AS UNSIGNED) as total_quantity')
                )
                ->groupBy($productTable . '.id', $productTable . '.name')
                ->get();
        }

        // Combine all sales by product
        $allSales = collect();
        
        // Add order sales
        foreach ($orderSales as $sale) {
            $allSales->push($sale);
        }
        
        // Add distribution sales
        foreach ($distributionSales as $sale) {
            $allSales->push($sale);
        }
        
        // Add agent sales
        foreach ($agentSales as $sale) {
            $allSales->push($sale);
        }

        // Group by product ID and sum quantities
        $combined = $allSales
            ->groupBy('id')
            ->map(function ($group) {
                return [
                    'id' => $group->first()->id,
                    'model' => $group->first()->model,
                    'total_quantity' => (int) $group->sum('total_quantity'),
                ];
            })
            ->sortByDesc('total_quantity')
            ->take($limit)
            ->values()
            ->all();

        return $combined;
    }

    /**
     * Get total buy price of all purchases (regardless of status).
     */
    public function totalPurchaseBuyPrice(): float
    {
        return (float) Purchase::sum(DB::raw('quantity * unit_price'));
    }

    /**
     * Get total products count in all purchases.
     */
    public function totalProductsInPurchases(): int
    {
        return (int) Purchase::sum('quantity');
    }
}
