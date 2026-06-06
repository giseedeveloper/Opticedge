<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class SuperadminSubscriptionProfitApiController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::query()
            ->withCount('tenants')
            ->orderBy('name')
            ->get()
            ->map(fn (Package $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'price' => (float) $package->price,
                'profit' => (float) $package->profit,
                'interval' => $package->interval,
                'interval_label' => $package->intervalLabel(),
                'is_active' => (bool) $package->is_active,
                'tenants_count' => $package->tenants_count,
                'estimated_monthly_revenue' => $package->estimatedMonthlyRevenue(),
                'estimated_monthly_profit' => $package->estimatedMonthlyProfit(),
            ]);

        $activeSubscriptions = Tenant::query()
            ->with('package:id,name,price,profit,interval,is_active')
            ->where('status', 'active')
            ->whereNotNull('package_id')
            ->orderBy('name')
            ->get()
            ->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status,
                'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
                'package' => $tenant->package ? [
                    'id' => $tenant->package->id,
                    'name' => $tenant->package->name,
                    'price' => (float) $tenant->package->price,
                    'profit' => (float) $tenant->package->profit,
                    'interval' => $tenant->package->interval,
                    'interval_label' => $tenant->package->intervalLabel(),
                ] : null,
            ]);

        $monthlyRevenue = $packages->sum('estimated_monthly_revenue');
        $monthlyProfit = $packages->sum('estimated_monthly_profit');

        return response()->json([
            'data' => [
                'packages' => $packages,
                'active_subscriptions' => $activeSubscriptions,
                'monthly_revenue' => $monthlyRevenue,
                'monthly_profit' => $monthlyProfit,
            ],
        ]);
    }
}
