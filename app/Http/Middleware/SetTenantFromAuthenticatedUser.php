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
            } else {
                $tenantId = self::resolveTenantIdForHierarchyUser($user);

                if ($tenantId !== null) {
                    TenantContext::set($tenantId);
                }
            }
        }

        return $next($request);
    }

    /**
     * Resolve tenant for legacy accounts missing tenant_id (admin or field roles).
     */
    private static function resolveTenantIdForHierarchyUser($user): ?int
    {
        if (in_array($user->role, ['admin', 'subadmin'], true)) {
            return Schema::hasTable('tenants')
                ? Tenant::orderBy('id')->value('id')
                : null;
        }

        if (! in_array($user->role, ['teamleader', 'regional_manager', 'agent'], true)) {
            return null;
        }

        if ($user->role === 'agent' && $user->team_leader_id) {
            $teamLeader = $user->teamLeader()->withoutGlobalScopes()->first(['id', 'tenant_id', 'regional_manager_id']);
            if ($teamLeader?->tenant_id !== null) {
                return (int) $teamLeader->tenant_id;
            }
            if ($teamLeader?->regional_manager_id) {
                $regionalManager = $teamLeader->regionalManager()->withoutGlobalScopes()->first(['id', 'tenant_id']);
                if ($regionalManager?->tenant_id !== null) {
                    return (int) $regionalManager->tenant_id;
                }
            }
        }

        if ($user->role === 'teamleader' && $user->regional_manager_id) {
            $regionalManager = $user->regionalManager()->withoutGlobalScopes()->first(['id', 'tenant_id']);
            if ($regionalManager?->tenant_id !== null) {
                return (int) $regionalManager->tenant_id;
            }
        }

        return Schema::hasTable('tenants')
            ? Tenant::orderBy('id')->value('id')
            : null;
    }
}
