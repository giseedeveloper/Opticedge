<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TenantSchema
{
    /** @var array<string, bool> */
    private static array $hasTenantColumn = [];

    public static function tableHasTenantId(Model|string $table): bool
    {
        $name = $table instanceof Model ? $table->getTable() : $table;

        if (! array_key_exists($name, self::$hasTenantColumn)) {
            self::$hasTenantColumn[$name] = Schema::hasTable($name)
                && Schema::hasColumn($name, 'tenant_id');
        }

        return self::$hasTenantColumn[$name];
    }

    public static function clearCache(): void
    {
        self::$hasTenantColumn = [];
    }
}
