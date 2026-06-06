<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperadminTenantApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $paginator = Tenant::with('package:id,name,price,interval')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Tenant $t) => $this->serialize($t))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load('package:id,name,price,interval,is_active');

        return response()->json(['data' => $this->serialize($tenant, detailed: true)]);
    }

    public function store(Request $request): JsonResponse
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

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'brand_name' => $validated['brand_name'] ?? $validated['name'],
            'package_id' => $validated['package_id'] ?? null,
            'status' => $validated['status'],
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
        ]);

        $tenant->load('package:id,name,price,interval');

        return response()->json([
            'message' => 'Vendor created.',
            'data' => $this->serialize($tenant, detailed: true),
        ], 201);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
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
        $tenant->load('package:id,name,price,interval');

        return response()->json([
            'message' => 'Vendor updated.',
            'data' => $this->serialize($tenant->fresh(), detailed: true),
        ]);
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        $tenant->update(['status' => 'suspended']);

        return response()->json([
            'message' => 'Vendor suspended.',
            'data' => $this->serialize($tenant->fresh()),
        ]);
    }

    public function formData(): JsonResponse
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price', 'interval']);

        return response()->json(['data' => ['packages' => $packages]]);
    }

    private function serialize(Tenant $tenant, bool $detailed = false): array
    {
        $package = $tenant->relationLoaded('package') ? $tenant->package : null;

        $data = [
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

        if ($detailed && $package) {
            $data['package'] = [
                'id' => $package->id,
                'name' => $package->name,
                'price' => $package->price,
                'interval' => $package->interval,
                'billing' => $package->formattedPrice().' / '.$package->intervalLabel(),
            ];
        }

        return $data;
    }
}
