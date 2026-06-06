<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Region;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class SuperadminDashboardApiController extends Controller
{
    public function index(): JsonResponse
    {
        $stats = [
            'tenants_total' => Tenant::count(),
            'tenants_active' => Tenant::where('status', 'active')->count(),
            'tenants_suspended' => Tenant::where('status', 'suspended')->count(),
            'packages' => Package::count(),
            'regions' => Region::count(),
            'brands' => Category::where('is_platform', true)->count(),
            'models' => Product::where('is_platform', true)->count(),
        ];

        $recentTenants = Tenant::with('package:id,name,price,interval')
            ->latest()
            ->take(8)
            ->get()
            ->map(fn (Tenant $tenant) => $this->serializeTenant($tenant));

        return response()->json([
            'data' => [
                'stats' => $stats,
                'recent_tenants' => $recentTenants,
            ],
        ]);
    }

    private function serializeTenant(Tenant $tenant): array
    {
        $package = $tenant->package;

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'brand_name' => $tenant->brand_name,
            'status' => $tenant->status,
            'package_id' => $tenant->package_id,
            'package_name' => $package?->name,
            'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
            'created_at' => $tenant->created_at?->toIso8601String(),
        ];
    }
}
