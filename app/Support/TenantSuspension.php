<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;

class TenantSuspension
{
    public const WORKER_ROLES = [
        'agent',
        'teamleader',
        'regional_manager',
        'subadmin',
    ];

    /**
     * @return list<string>
     */
    public static function adminAllowedRouteNames(): array
    {
        return [
            'admin.tenant.edit',
            'admin.tenant.subscribe',
            'admin.tenant.subscribe.processing',
            'admin.tenant.subscribe.status',
            'admin.tenant.subscribe.success',
            'admin.profile',
            'logout',
        ];
    }

    public static function tenantForUser(?User $user): ?Tenant
    {
        if ($user === null || $user->tenant_id === null) {
            return null;
        }

        return Tenant::query()->find($user->tenant_id);
    }

    public static function isSuspendedForUser(?User $user): bool
    {
        $tenant = self::tenantForUser($user);

        return $tenant !== null && $tenant->status === 'suspended';
    }

    public static function blocksLoginForUser(User $user): ?string
    {
        if (! self::isSuspendedForUser($user)) {
            return null;
        }

        if ($user->role === 'admin') {
            return null;
        }

        if (in_array($user->role, self::WORKER_ROLES, true)) {
            return 'Your vendor account is suspended. Only the vendor admin can sign in to renew the subscription. Please contact your administrator.';
        }

        if ($user->tenant_id !== null) {
            return 'This vendor account is suspended. Sign-in is not available until the subscription is renewed.';
        }

        return null;
    }

    public static function adminHasRestrictedAccess(?User $user): bool
    {
        return $user !== null
            && $user->role === 'admin'
            && self::isSuspendedForUser($user);
    }
}
