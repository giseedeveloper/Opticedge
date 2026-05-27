<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return list<string>
     */
    private function catalogTables(): array
    {
        $tables = [];

        if (Schema::hasTable('regions')) {
            $tables[] = 'regions';
        }

        if (Schema::hasTable('brands')) {
            $tables[] = 'brands';
        } elseif (Schema::hasTable('categories')) {
            $tables[] = 'categories';
        }

        if (Schema::hasTable('models')) {
            $tables[] = 'models';
        } elseif (Schema::hasTable('products')) {
            $tables[] = 'products';
        }

        return $tables;
    }

    public function up(): void
    {
        foreach ($this->catalogTables() as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'is_platform')) {
                    $blueprint->boolean('is_platform')->default(false);
                }
                if (! Schema::hasColumn($table, 'created_by_tenant_id') && Schema::hasTable('tenants')) {
                    $blueprint->foreignId('created_by_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                }
            });

            if (Schema::hasColumn($table, 'is_platform')) {
                DB::table($table)->update(['is_platform' => true]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->catalogTables() as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'created_by_tenant_id')) {
                    $blueprint->dropConstrainedForeignId('created_by_tenant_id');
                }
                if (Schema::hasColumn($table, 'is_platform')) {
                    $blueprint->dropColumn('is_platform');
                }
            });
        }
    }
};
