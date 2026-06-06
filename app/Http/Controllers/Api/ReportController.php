<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Admin\ReportController as WebReportController;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->query('branch_id');

        $totalSales = (float) Order::sum('total_price');
        $totalOrders = Order::count();
        $totalCustomers = User::where('role', 'customer')->count();

        $salesData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $salesData[$date] = $this->dailySalesAmount($date);
        }

        $branchesBusiness = Branch::orderBy('name')
            ->get()
            ->map(function ($b) {
                $purchases = Purchase::where('branch_id', $b->id)->get();
                $purchaseTotal = (float) $purchases->sum(function ($p) {
                    return (float) ($p->total_amount ?? ($p->quantity * $p->unit_price));
                });
                $salesCount = $this->branchAttributedSalesCount($b->id);
                $closingStock = ProductListItem::query()
                    ->whereEffectiveBranch($b->id)
                    ->whereNull('sold_at')
                    ->count();
                $openingStock = $closingStock + $salesCount;

                return [
                    'branch_id' => $b->id,
                    'name' => $b->name,
                    'purchase_total' => $purchaseTotal,
                    'purchase_count' => $purchases->count(),
                    'opening_stock' => $openingStock,
                    'sales_count' => $salesCount,
                    'closing_stock' => $closingStock,
                ];
            })
            ->values()
            ->all();

        $unassignedPurchases = Purchase::whereNull('branch_id')->count();
        $unassignedSales = $this->unassignedAttributedSalesCount();
        $unassignedClosingStock = ProductListItem::query()
            ->whereNull('sold_at')
            ->whereNull('branch_id')
            ->where(function ($outer) {
                $outer->whereNull('purchase_id')
                    ->orWhereHas('purchase', fn ($p) => $p->whereNull('branch_id'));
            })
            ->count();
        $unassignedOpeningStock = $unassignedClosingStock + $unassignedSales;

        $payload = [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'total_customers' => $totalCustomers,
            'sales_by_day' => $salesData,
            'branches_business' => $branchesBusiness,
            'unassigned_stock' => [
                'opening_stock' => $unassignedOpeningStock,
                'purchase_count' => $unassignedPurchases,
                'sales_count' => $unassignedSales,
                'closing_stock' => $unassignedClosingStock,
            ],
        ];

        if ($branchId !== null && $branchId !== '') {
            $bid = (int) $branchId;
            $purchaseQuery = Purchase::where('branch_id', $bid);
            $purchaseTotal = (float) (clone $purchaseQuery)->get()->sum(function ($p) {
                return (float) ($p->total_amount ?? ($p->quantity * $p->unit_price));
            });
            $purchaseCount = (clone $purchaseQuery)->count();
            $salesCount = ProductListItem::query()
                ->whereNotNull('sold_at')
                ->where(function ($outer) use ($bid) {
                    $outer->whereHas('agentSale.agent', fn ($q) => $q->where('branch_id', $bid))
                        ->orWhereHas('pendingSale.seller', fn ($q) => $q->where('branch_id', $bid))
                        ->orWhereHas('agentCredit.agent', fn ($q) => $q->where('branch_id', $bid))
                        ->orWhere(function ($shop) use ($bid) {
                            $shop->whereDoesntHave('agentSale', fn ($q) => $q->whereNotNull('agent_id'))
                                ->whereDoesntHave('pendingSale', fn ($q) => $q->whereNotNull('seller_id'))
                                ->whereDoesntHave('agentCredit')
                                ->whereEffectiveBranch($bid);
                        });
                })
                ->count();
            $closingStock = ProductListItem::query()
                ->whereEffectiveBranch($bid)
                ->whereNull('sold_at')
                ->count();
            $openingStock = $closingStock + $salesCount;
            $payload['branch_id'] = $bid;
            $payload['branch_purchase_total'] = $purchaseTotal;
            $payload['branch_purchase_count'] = $purchaseCount;
            $payload['branch_opening_stock'] = $openingStock;
            $payload['branch_sales_count'] = $salesCount;
            $payload['branch_closing_stock'] = $closingStock;
        }

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function branchDetail(int $branchId)
    {
        $branch = Branch::findOrFail($branchId);
        $purchases = Purchase::with('product.category')
            ->where('branch_id', $branchId)
            ->latest('date')
            ->take(100)
            ->get()
            ->map(function ($p) {
                $total = (float) ($p->total_amount ?? ($p->quantity * $p->unit_price));
                return [
                    'id' => $p->id,
                    'name' => $p->name ?? 'Purchase #'.$p->id,
                    'date' => $p->date?->format('Y-m-d'),
                    'product_name' => $p->product?->name ?? '–',
                    'category_name' => $p->product?->category?->name ?? '–',
                    'quantity' => (int) ($p->quantity ?? 0),
                    'total_amount' => $total,
                    'paid_amount' => (float) ($p->paid_amount ?? 0),
                    'payment_status' => $p->payment_status ?? '–',
                ];
            })
            ->values()
            ->all();

        // Last 30 days: units sold in this branch’s stock scope + revenue (aligned with branch attribution rules).
        $salesWindowDays = 30;
        $since = Carbon::now()->subDays($salesWindowDays)->startOfDay();
        $agents = $this->branchAgentsWithSalesMetrics($branchId, $since);

        return response()->json([
            'data' => [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'purchases' => $purchases,
                'sales_metrics_days' => $salesWindowDays,
                'agents' => $agents,
            ],
        ]);
    }

    /**
     * Agents assigned to the branch plus any rep with a sale in branch-scoped stock in $since..now.
     * sales_units = IMEI/unit rows sold in that window; revenue_tzs = sum of linked sale amounts (cash + pending + credit paths).
     *
     * @return array<int, array<string, mixed>>
     */
    private function branchAgentsWithSalesMetrics(int $branchId, Carbon $since): array
    {
        if (! Schema::hasTable('product_list')) {
            return [];
        }

        $activityRows = ProductListItem::query()
            ->whereNotNull('sold_at')
            ->where('sold_at', '>=', $since)
            ->whereEffectiveBranch($branchId)
            ->where(function ($o) {
                $o->whereHas('agentSale', fn ($q) => $q->whereNotNull('agent_id'))
                    ->orWhereHas('pendingSale', fn ($q) => $q->whereNotNull('seller_id'))
                    ->orWhereHas('agentCredit', fn ($q) => $q->whereNotNull('agent_id'));
            })
            ->with([
                'agentSale:id,agent_id,selling_price,total_selling_value',
                'pendingSale:id,seller_id,selling_price,total_selling_value',
                'agentCredit:id,agent_id,total_amount',
            ])
            ->get(['id', 'agent_sale_id', 'pending_sale_id', 'agent_credit_id']);

        $byAgent = [];
        foreach ($activityRows as $pl) {
            $aid = (int) ($pl->agentSale?->agent_id
                ?? $pl->pendingSale?->seller_id
                ?? $pl->agentCredit?->agent_id);
            if ($aid <= 0) {
                continue;
            }
            if (! isset($byAgent[$aid])) {
                $byAgent[$aid] = ['units' => 0, 'revenue' => 0.0];
            }
            $byAgent[$aid]['units']++;
            if ($pl->agent_sale_id && $pl->agentSale) {
                $ag = $pl->agentSale;
                $byAgent[$aid]['revenue'] += (float) ($ag->selling_price ?? $ag->total_selling_value ?? 0);
            } elseif ($pl->pending_sale_id && $pl->pendingSale) {
                $ps = $pl->pendingSale;
                $byAgent[$aid]['revenue'] += (float) ($ps->selling_price ?? $ps->total_selling_value ?? 0);
            } elseif ($pl->agent_credit_id && $pl->agentCredit) {
                $byAgent[$aid]['revenue'] += (float) ($pl->agentCredit->total_amount ?? 0);
            }
        }

        $assignedIds = [];
        if (Schema::hasColumn('users', 'branch_id')) {
            $assignedIds = User::query()
                ->where('role', 'agent')
                ->where(function ($q) {
                    $q->where('status', 'active')->orWhereNull('status');
                })
                ->where('branch_id', $branchId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $allIds = collect($assignedIds)->merge(array_keys($byAgent))->unique()->sort()->values();
        if ($allIds->isEmpty()) {
            return [];
        }

        $users = User::query()
            ->whereIn('id', $allIds->all())
            ->where('role', 'agent')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        $out = [];
        foreach ($users as $u) {
            $uid = (int) $u->id;
            $m = $byAgent[$uid] ?? ['units' => 0, 'revenue' => 0.0];
            $out[] = [
                'id' => $uid,
                'name' => $u->name,
                'branch_id' => $u->branch_id !== null ? (int) $u->branch_id : null,
                'sales_units' => $m['units'],
                'revenue_tzs' => round($m['revenue'], 2),
            ];
        }

        return $out;
    }

    private function dailySalesAmount(string $date): float
    {
        $total = (float) Order::whereDate('created_at', $date)->sum('total_price');

        if (Schema::hasTable('distribution_sales')) {
            $total += (float) DB::table('distribution_sales')
                ->whereDate('date', $date)
                ->sum('total_selling_value');
        }

        if (Schema::hasTable('agent_sales')) {
            $total += (float) DB::table('agent_sales')
                ->whereDate('date', $date)
                ->sum('total_selling_value');
        }

        if (Schema::hasTable('agent_credits')) {
            $total += (float) DB::table('agent_credits')
                ->whereDate('date', $date)
                ->sum('total_amount');
        }

        return $total;
    }

    private function branchAttributedSalesCount(int $branchId): int
    {
        return ProductListItem::query()
            ->whereNotNull('sold_at')
            ->where(function ($outer) use ($branchId) {
                $outer->whereHas('agentSale.agent', fn ($q) => $q->where('branch_id', $branchId))
                    ->orWhereHas('pendingSale.seller', fn ($q) => $q->where('branch_id', $branchId))
                    ->orWhereHas('agentCredit.agent', fn ($q) => $q->where('branch_id', $branchId))
                    ->orWhere(function ($shop) use ($branchId) {
                        $shop->whereDoesntHave('agentSale', fn ($q) => $q->whereNotNull('agent_id'))
                            ->whereDoesntHave('pendingSale', fn ($q) => $q->whereNotNull('seller_id'))
                            ->whereDoesntHave('agentCredit')
                            ->whereEffectiveBranch($branchId);
                    });
            })
            ->count();
    }

    private function unassignedAttributedSalesCount(): int
    {
        return ProductListItem::query()
            ->whereNotNull('sold_at')
            ->where(function ($outer) {
                $outer->whereHas('agentSale', function ($q) {
                    $q->whereNull('agent_id')
                        ->orWhereHas('agent', fn ($u) => $u->whereNull('branch_id'));
                })->orWhereHas('pendingSale', function ($q) {
                    $q->whereNull('seller_id')
                        ->orWhereHas('seller', fn ($u) => $u->whereNull('branch_id'));
                })->orWhereHas('agentCredit', function ($q) {
                    $q->whereNull('agent_id')
                        ->orWhereHas('agent', fn ($u) => $u->whereNull('branch_id'));
                })->orWhere(function ($shop) {
                    $shop->whereDoesntHave('agentSale', fn ($q) => $q->whereNotNull('agent_id'))
                        ->whereDoesntHave('pendingSale', fn ($q) => $q->whereNotNull('seller_id'))
                        ->whereDoesntHave('agentCredit')
                        ->whereNull('branch_id')
                        ->where(function ($w) {
                            $w->whereNull('purchase_id')
                                ->orWhereHas('purchase', fn ($p) => $p->whereNull('branch_id'));
                        });
                });
            })
            ->count();
    }

    public function exportAgentStock(Request $request)
    {
        return app(WebReportController::class)->exportAgentDailyStock($request);
    }
}
