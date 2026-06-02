<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use App\Support\TenantSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
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

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
