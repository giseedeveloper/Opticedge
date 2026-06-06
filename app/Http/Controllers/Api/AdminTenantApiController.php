<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTenantApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenant->load('package:id,name,price,interval');

        $package = $tenant->package;

        return response()->json([
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'brand_name' => $tenant->brand_name,
                'package_name' => $package?->name,
                'billing' => $package
                    ? $package->formattedPrice().' / '.$package->intervalLabel()
                    : null,
                'status' => $tenant->status,
                'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
                'subscription_ends_at_formatted' => $tenant->subscription_ends_at?->format('M j, Y'),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug,'.$tenant->id,
            'brand_name' => 'nullable|string|max:255',
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'brand_name' => $validated['brand_name'] ?? $validated['name'],
        ]);

        return response()->json([
            'message' => 'Vendor profile updated.',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'brand_name' => $tenant->brand_name,
            ],
        ]);
    }

    private function resolveTenant(Request $request): Tenant
    {
        $user = $request->user();
        if (! $user?->tenant_id) {
            abort(403, 'Your account is not linked to a vendor.');
        }

        return Tenant::query()->whereKey($user->tenant_id)->firstOrFail();
    }
}
