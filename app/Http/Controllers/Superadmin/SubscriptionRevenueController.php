<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Selcompay;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubscriptionRevenueController extends Controller
{
    /**
     * Actual vendor-subscription revenue and profit, reconciled from completed
     * Selcom payments (as opposed to the estimate page which multiplies the
     * manually-entered package profit by the active-tenant count).
     *
     * Revenue = the amount each vendor actually paid. Profit is apportioned from
     * the package's declared profit margin (profit / price) applied to the amount
     * paid, so discounts/partial amounts stay proportional. Demo payments (no real
     * money) are excluded from the money totals.
     */
    public function index(Request $request): View
    {
        $from = $this->parseDate($request->query('from'));
        $to = $this->parseDate($request->query('to'));

        $rows = DB::table('selcompays as sp')
            ->join('vendor_registration_intents as vri', function ($join) {
                $join->on('sp.payout_source_id', '=', 'vri.id')
                    ->where('sp.payout_source_type', '=', 'vendor_registration_intent');
            })
            ->leftJoin('packages as p', 'vri.package_id', '=', 'p.id')
            ->leftJoin('tenants as t', 'vri.tenant_id', '=', 't.id')
            ->where('sp.purpose', Selcompay::PURPOSE_VENDOR_SUBSCRIPTION)
            ->where('sp.payment_status', 'completed')
            ->where('sp.transid', 'not like', 'DEMO%')
            ->when($from, fn ($q) => $q->whereDate('sp.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('sp.created_at', '<=', $to))
            ->orderByDesc('sp.created_at')
            ->get([
                'sp.id',
                'sp.amount',
                'sp.created_at',
                'sp.transid',
                'vri.intent_type',
                'vri.vendor_name as intent_vendor_name',
                'p.name as package_name',
                'p.price as package_price',
                'p.profit as package_profit',
                't.name as tenant_name',
            ]);

        foreach ($rows as $row) {
            $amount = (float) $row->amount;
            $price = (float) $row->package_price;
            $declaredProfit = (float) $row->package_profit;

            $row->amount = $amount;
            $row->profit = $price > 0
                ? round($amount * ($declaredProfit / $price), 2)
                : $declaredProfit;
            $row->vendor_display = $row->tenant_name ?: ($row->intent_vendor_name ?: '—');
            $row->package_display = $row->package_name ?: '—';
            $row->intent_type = $row->intent_type ?: 'registration';
        }

        $totalRevenue = (float) $rows->sum('amount');
        $totalProfit = (float) $rows->sum('profit');
        $paymentsCount = $rows->count();
        $renewalsCount = $rows->where('intent_type', 'renewal')->count();
        $registrationsCount = $paymentsCount - $renewalsCount;

        $byPackage = $rows
            ->groupBy('package_display')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'payments' => $group->count(),
                'revenue' => (float) $group->sum('amount'),
                'profit' => (float) $group->sum('profit'),
            ])
            ->sortByDesc('revenue')
            ->values();

        $byVendor = $rows
            ->groupBy('vendor_display')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'payments' => $group->count(),
                'revenue' => (float) $group->sum('amount'),
                'profit' => (float) $group->sum('profit'),
            ])
            ->sortByDesc('revenue')
            ->values();

        $payments = $rows->take(200);

        return view('superadmin.subscription-revenue.index', compact(
            'totalRevenue',
            'totalProfit',
            'paymentsCount',
            'registrationsCount',
            'renewalsCount',
            'byPackage',
            'byVendor',
            'payments',
            'from',
            'to',
        ));
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
