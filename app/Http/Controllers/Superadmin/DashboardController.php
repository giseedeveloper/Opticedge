<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Region;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function __invoke()
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

        $recentTenants = Tenant::with('package')->latest()->take(8)->get();

        return view('superadmin.dashboard', compact('stats', 'recentTenants'));
    }
}
