<?php

namespace App\Models\Concerns;

/**
 * Tenant-owned rows: when no tenant context is set, queries return nothing (fail closed).
 */
trait BelongsToTenantStrict
{
    use BelongsToTenant;

    public function usesStrictTenantScope(): bool
    {
        return true;
    }
}
