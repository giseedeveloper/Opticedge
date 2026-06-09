<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromAuthenticatedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::clear();

        $user = $request->user();
        if ($user) {
            if ($user->isSuperadmin()) {
                TenantContext::bypass();
            } elseif ($user->tenant_id !== null) {
                TenantContext::set($user->tenant_id);
            } elseif (in_array($user->role, ['admin', 'subadmin'], true)) {
                // Legacy admin account created before tenant_id was added to users.
                // On single-vendor installs the only tenant is the correct owner.
                $tenantId = Schema::hasTable('tenants')
                    ? Tenant::orderBy('id')->value('id')
                    : null;

                if ($tenantId !== null) {
                    TenantContext::set($tenantId);
                }
            }
        }

        return $next($request);
    }
}
