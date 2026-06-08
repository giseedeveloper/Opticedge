<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use App\Support\TenantSuspension;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function edit(Request $request)
    {
        $tenant = $this->resolveTenant($request);
        $tenant->load('package');

        $packages = Package::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $tenantSuspended = TenantSuspension::isSuspendedForUser($request->user());

        return view('admin.tenant.edit', compact('tenant', 'packages', 'tenantSuspended'));
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
