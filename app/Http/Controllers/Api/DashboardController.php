<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\Payable;
use App\Models\User;
use App\Models\AgentProductListAssignment;
use App\Services\DashboardFinancialService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardFinancialService $financialService
    ) {}

    /**
     * Get dashboard metrics for admin (same as web: stats, sales metrics, financial, payment options, top products, recent orders).
     */
    public function index(Request $request)
    {
        $user = $request->user() ?? Auth::user();
        if ($user?->isSuperadmin()) {
            TenantContext::bypass();
        } elseif ($user?->tenant_id !== null) {
            TenantContext::set((int) $user->tenant_id);
        }

        $totalCustomers = User::where('role', 'customer')->count();
        $totalOrders = Order::count();
        $totalProducts = Product::count();

        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'customer_name' => $order->user->name ?? 'Guest',
                    'total_price' => (float) $order->total_price,
                    'status' => $order->status,
                    'created_at' => $order->created_at ? (is_string($order->created_at) ? Carbon::parse($order->created_at)->toISOString() : $order->created_at->toISOString()) : null,
                ];
            });

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->subMonth()->startOfDay();
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $financialMetrics = $this->financialService->getMetrics($startDate, $endDate);
        $salesMetrics = $this->financialService->getSalesMetrics();
        $distributionReceivables = $this->financialService->distributionReceivables($startDate, $endDate);
        $agentCreditReceivables = $this->financialService->getAgentCreditReceivableSummary($startDate, $endDate);
        $distributorReceivablesBreakdown = $this->financialService->getDistributorReceivableBreakdown($startDate, $endDate);
        $topProducts = $this->financialService->getTopSellingProducts($startDate, $endDate, 10);

        $agentAgingAssets = ProductListItem::query()
            ->with(['agentProductListAssignment.agent:id,name', 'product:id,name'])
            ->whereNull('sold_at')
            ->whereHas('agentProductListAssignment')
            ->addSelect([
                'assigned_at' => AgentProductListAssignment::query()
                    ->select('created_at')
                    ->whereColumn('agent_product_list_assignments.product_list_id', 'product_list.id')
                    ->limit(1),
            ])
            ->orderBy('assigned_at', 'asc')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                $assignedAt = $item->assigned_at ? Carbon::parse($item->assigned_at) : null;
                $agingDays = $assignedAt
                    ? max(0, (int) floor($assignedAt->floatDiffInRealDays(Carbon::now()->startOfDay())))
                    : null;

                return [
                    'id' => $item->id,
                    'imei_number' => $item->imei_number,
                    'model' => $item->model ?? $item->product?->name,
                    'agent_name' => $item->agentProductListAssignment?->agent?->name,
                    'assigned_at' => $assignedAt?->toDateString(),
                    'aging_days' => $agingDays,
                ];
            });

        $overduePurchases = Purchase::with(['product', 'branch'])
            ->where('payment_status', '!=', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->limit(20)
            ->get()
            ->map(function ($purchase) {
                $total = (float) ($purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price));
                $paid = (float) ($purchase->paid_amount ?? 0);
                $pending = max(0, $total - $paid);
                $date = $purchase->date ? Carbon::parse($purchase->date) : null;
                $agingDays = $date
                    ? max(0, (int) floor($date->floatDiffInRealDays(Carbon::now()->startOfDay())))
                    : null;

                return [
                    'id' => $purchase->id,
                    'name' => $purchase->name ?? 'Purchase #'.$purchase->id,
                    'date' => $purchase->date,
                    'branch_name' => $purchase->branch?->name,
                    'product_name' => $purchase->product?->name,
                    'pending_amount' => $pending,
                    'aging_days' => $agingDays,
                ];
            });

        $overduePayables = Payable::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->limit(20)
            ->get()
            ->map(function ($payable) {
                $date = $payable->date ? Carbon::parse($payable->date) : null;
                $agingDays = $date
                    ? max(0, (int) floor($date->floatDiffInRealDays(Carbon::now()->startOfDay())))
                    : null;

                return [
                    'id' => $payable->id,
                    'description' => $payable->description ?? $payable->activity ?? 'Payable',
                    'amount' => (float) ($payable->amount ?? 0),
                    'date' => $payable->date,
                    'aging_days' => $agingDays,
                ];
            });

        $paymentOptions = PaymentOption::visible()->orderBy('name')->get()->map(function ($opt) {
            return [
                'id' => $opt->id,
                'name' => $opt->name,
                'type' => $opt->type,
                'balance' => (float) $opt->balance,
                'opening_balance' => (float) $opt->opening_balance,
            ];
        });

        return response()->json([
            'data' => [
                'total_customers' => $totalCustomers,
                'total_orders' => $totalOrders,
                'total_products' => $totalProducts,
                'recent_orders' => $recentOrders,
                'financial_metrics' => $financialMetrics,
                'receivables_breakdown' => [
                    'distribution' => $distributionReceivables,
                    'agent_credit' => $agentCreditReceivables,
                    'distributor_detail' => $distributorReceivablesBreakdown,
                ],
                'agent_aging_assets' => $agentAgingAssets,
                'agent_aging_assets_count' => $agentAgingAssets->count(),
                'overdue_purchases' => $overduePurchases,
                'overdue_payables' => $overduePayables,
                'sales_metrics' => $salesMetrics,
                'payment_options' => $paymentOptions,
                'top_products' => $topProducts,
            ],
        ]);
    }
}
