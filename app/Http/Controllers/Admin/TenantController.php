<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function edit(Request $request)
    {
        $tenant = $this->resolveTenant($request);
        $tenant->load('package');

        return view('admin.tenant.edit', compact('tenant'));
    }

    public function update(Request $request)
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

        return redirect()
            ->route('admin.tenant.edit')
            ->with('success', 'Vendor profile updated.');
    }

    private function resolveTenant(Request $request): Tenant
    {
        $user = $request->user();

        if (! $user?->tenant_id) {
            abort(403, 'Your account is not linked to a vendor.');
        }

        return Tenant::query()
            ->whereKey($user->tenant_id)
            ->firstOrFail();
    }
}
