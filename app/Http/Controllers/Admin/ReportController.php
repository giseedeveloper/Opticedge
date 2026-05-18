<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\User;
use App\Services\AgentDailyStockReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->query('date_from'))->startOfDay()
            : ($request->filled('report_date')
                ? Carbon::parse($request->query('report_date'))->startOfDay()
                : Carbon::today());
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->query('date_to'))->endOfDay()
            : ($request->filled('report_date')
                ? Carbon::parse($request->query('report_date'))->endOfDay()
                : $dateFrom->copy()->endOfDay());
        if ($dateTo->lt($dateFrom)) {
            $dateTo = $dateFrom->copy()->endOfDay();
        }

        // Basic Stats
        $totalSales = Order::sum('total_price');
        $totalOrders = Order::count();
        $totalCustomers = User::where('role', 'customer')->count();

        // Sales chart data (Last 7 days)
        $salesData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $salesData[$date] = $this->dailySalesAmount($date);
        }

        $branchesBusiness = collect();
        $unassignedPurchases = 0;
        $unassignedPurchaseTotal = 0.0;

        if (Schema::hasTable('branches') && Schema::hasColumn('purchases', 'branch_id')) {
            $branchesBusiness = Branch::orderBy('name')
                ->get()
                ->map(function (Branch $b) {
                    $rows = Purchase::where('branch_id', $b->id)->get();
                    $total = (float) $rows->sum(function ($p) {
                        return (float) ($p->total_amount ?? ($p->quantity * $p->unit_price));
                    });
                    $salesCount = $this->branchAttributedSalesCount($b->id);
                    $closingStock = ProductListItem::query()
                        ->whereEffectiveBranch($b->id)
                        ->whereNull('sold_at')
                        ->count();
                    $openingStock = $closingStock + $salesCount;

                    return (object) [
                        'id' => $b->id,
                        'name' => $b->name,
                        'purchase_count' => $rows->count(),
                        'purchase_total' => $total,
                        'opening_stock' => $openingStock,
                        'sales_count' => $salesCount,
                        'closing_stock' => $closingStock,
                    ];
                });

            $noBranch = Purchase::whereNull('branch_id')->get();
            $unassignedPurchaseQuantity = $noBranch->sum('quantity');
            $unassignedPurchases = $noBranch->count();
            $unassignedPurchaseTotal = (float) $noBranch->sum(function ($p) {
                return (float) ($p->total_amount ?? ($p->quantity * $p->unit_price));
            });
        }

        $selectedBranchId = $request->query('branch_id');
        $agentBranchFilter = ($selectedBranchId !== null && $selectedBranchId !== '') ? (int) $selectedBranchId : null;
        $agentStockReport = app(AgentDailyStockReportService::class)->build($dateFrom, $dateTo, $agentBranchFilter);

        $selectedBranchDetail = null;
        if ($selectedBranchId !== null && $selectedBranchId !== '' && Schema::hasColumn('purchases', 'branch_id')) {
            $bid = (int) $selectedBranchId;
            $branch = Branch::find($bid);
            if ($branch) {
                $rows = Purchase::where('branch_id', $bid)->get();
                $salesCount = $this->branchAttributedSalesCount($bid);
                $closingStock = ProductListItem::query()
                    ->whereEffectiveBranch($bid)
                    ->whereNull('sold_at')
                    ->count();
                $openingStock = $closingStock + $salesCount;
                $selectedBranchDetail = (object) [
                    'branch' => $branch,
                    'purchase_count' => $rows->count(),
                    'purchase_total' => (float) $rows->sum(function ($p) {
                        return (float) ($p->total_amount ?? ($p->quantity * $p->unit_price));
                    }),
                    'opening_stock' => $openingStock,
                    'sales_count' => $salesCount,
                    'closing_stock' => $closingStock,
                ];
            }
        }

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

        $reportBranchOptions = Schema::hasTable('branches')
            ? Branch::orderBy('name')->get()
            : collect();

        return view('admin.reports.index', compact(
            'totalSales',
            'totalOrders',
            'totalCustomers',
            'salesData',
            'branchesBusiness',
            'unassignedPurchases',
            'unassignedPurchaseTotal',
            'selectedBranchId',
            'selectedBranchDetail',
            'unassignedSales',
            'unassignedOpeningStock',
            'unassignedClosingStock',
            'agentStockReport',
            'reportBranchOptions',
        ));
    }

    public function exportAgentDailyStock(Request $request): StreamedResponse
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->query('date_from'))->startOfDay()
            : ($request->filled('report_date')
                ? Carbon::parse($request->query('report_date'))->startOfDay()
                : Carbon::today());
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->query('date_to'))->endOfDay()
            : ($request->filled('report_date')
                ? Carbon::parse($request->query('report_date'))->endOfDay()
                : $dateFrom->copy()->endOfDay());
        if ($dateTo->lt($dateFrom)) {
            $dateTo = $dateFrom->copy()->endOfDay();
        }
        $branchRaw = $request->query('branch_id');
        $branchId = ($branchRaw !== null && $branchRaw !== '') ? (int) $branchRaw : null;

        $service = app(AgentDailyStockReportService::class);
        $payload = $service->build($dateFrom, $dateTo, $branchId);
        $lines = $service->rowsToCsvLines($payload);

        $filename = 'agent-opening-stock-'.$dateFrom->format('Y-m-d');
        if (! $dateFrom->isSameDay($dateTo)) {
            $filename .= '_to_'.$dateTo->format('Y-m-d');
        }
        if ($branchId !== null) {
            $filename .= '-branch-'.$branchId;
        }
        $filename .= '.csv';

        return response()->streamDownload(function () use ($lines) {
            echo "\xEF\xBB\xBF";
            foreach ($lines as $line) {
                echo $line."\r\n";
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
}
