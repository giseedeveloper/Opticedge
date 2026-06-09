<?php

namespace App\Models\Scopes;

use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\TenantSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class TenantScope implements Scope
{
    /** @var bool|null */
    private static ?bool $singleTenantInstall = null;

    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::shouldBypass()) {
            return;
        }

        if (! TenantSchema::tableHasTenantId($model)) {
            return;
        }

        $tenantId = TenantContext::id();

        if ($tenantId === null) {
            if (method_exists($model, 'usesStrictTenantScope') && $model->usesStrictTenantScope()) {
                $builder->whereRaw('1 = 0');
            }

            return;
        }

        $column = $model->getTable().'.tenant_id';

        if ($this->includeLegacyNullTenantRows()) {
            $builder->where(function (Builder $q) use ($column, $tenantId) {
                $q->where($column, $tenantId)->orWhereNull($column);
            });

            return;
        }

        $builder->where($column, $tenantId);
    }

    /**
     * Rows created before tenant_id backfill are NULL; on single-vendor installs they belong to the only tenant.
     */
    private function includeLegacyNullTenantRows(): bool
    {
        if (self::$singleTenantInstall !== null) {
            return self::$singleTenantInstall;
        }

        if (! Schema::hasTable('tenants')) {
            self::$singleTenantInstall = true;

            return true;
        }

        self::$singleTenantInstall = Tenant::count() <= 1;

        return self::$singleTenantInstall;
    }
}
