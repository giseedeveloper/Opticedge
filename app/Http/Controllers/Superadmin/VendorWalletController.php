<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Platform-wide view of every vendor's disbursement wallet: current balance,
 * lifetime deposits and payouts, and the grand total held across all wallets.
 */
class VendorWalletController extends Controller
{
    public function index(): View
    {
        $vendors = Tenant::query()
            ->leftJoin('tenant_wallets as w', 'w.tenant_id', '=', 'tenants.id')
            ->orderBy('tenants.name')
            ->get([
                'tenants.id',
                'tenants.name',
                'tenants.status',
                DB::raw('COALESCE(w.balance, 0) as balance'),
            ]);

        $deposits = WalletTransaction::query()
            ->where('type', WalletTransaction::TYPE_TOPUP)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(amount) as total')
            ->pluck('total', 'tenant_id');

        $payouts = WalletTransaction::query()
            ->where('type', WalletTransaction::TYPE_PAYOUT)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(amount) as total')
            ->pluck('total', 'tenant_id');

        foreach ($vendors as $vendor) {
            $vendor->balance = (float) $vendor->balance;
            $vendor->total_deposits = (float) ($deposits[$vendor->id] ?? 0);
            $vendor->total_payouts = (float) ($payouts[$vendor->id] ?? 0);
        }

        $totalBalance = (float) $vendors->sum('balance');
        $totalDeposits = (float) $vendors->sum('total_deposits');
        $totalPayouts = (float) $vendors->sum('total_payouts');
        $fundedCount = $vendors->where('balance', '>', 0)->count();

        return view('superadmin.vendor-wallets.index', compact(
            'vendors',
            'totalBalance',
            'totalDeposits',
            'totalPayouts',
            'fundedCount',
        ));
    }
}
