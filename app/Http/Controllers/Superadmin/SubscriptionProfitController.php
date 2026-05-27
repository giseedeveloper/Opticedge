<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;

class SubscriptionProfitController extends Controller
{
    public function index()
    {
        $packages = Package::query()
            ->withCount('tenants')
            ->orderBy('name')
            ->get();

        $activeSubscriptions = Tenant::query()
            ->with('package:id,name,price,profit,interval,is_active')
            ->where('status', 'active')
            ->whereNotNull('package_id')
            ->orderBy('name')
            ->get();

        $monthlyRevenue = $packages->sum(fn (Package $package) => $package->estimatedMonthlyRevenue());
        $monthlyProfit = $packages->sum(fn (Package $package) => $package->estimatedMonthlyProfit());

        return view('superadmin.subscription-profits.index', compact(
            'packages',
            'activeSubscriptions',
            'monthlyRevenue',
            'monthlyProfit',
        ));
    }
}
