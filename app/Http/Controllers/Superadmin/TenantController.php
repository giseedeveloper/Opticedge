<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('package')->orderBy('name')->get();

        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get();

        return view('superadmin.tenants.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tenants,slug',
            'brand_name' => 'nullable|string|max:255',
            'package_id' => 'nullable|exists:packages,id',
            'status' => 'required|in:active,suspended',
            'subscription_ends_at' => 'nullable|date',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        $base = $slug;
        $i = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        Tenant::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'brand_name' => $validated['brand_name'] ?? $validated['name'],
            'package_id' => $validated['package_id'] ?? null,
            'status' => $validated['status'],
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
        ]);

        return redirect()->route('superadmin.tenants.index')->with('success', 'Vendor created.');
    }

    public function edit(Tenant $tenant)
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get();

        return view('superadmin.tenants.edit', compact('tenant', 'packages'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug,'.$tenant->id,
            'brand_name' => 'nullable|string|max:255',
            'package_id' => 'nullable|exists:packages,id',
            'status' => 'required|in:active,suspended',
            'subscription_ends_at' => 'nullable|date',
        ]);

        $tenant->update($validated);

        return redirect()->route('superadmin.tenants.index')->with('success', 'Vendor updated.');
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->update(['status' => 'suspended']);

        return redirect()->route('superadmin.tenants.index')->with('success', 'Vendor suspended.');
    }
}
