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
    private function operationalTables(): array
    {
        return array_values(array_filter([
            'stocks',
            'purchases',
            'product_list',
            'branches',
            'vendors',
            'payment_options',
            'expenses',
            'orders',
            'agent_sales',
            'distribution_sales',
            'shop_records',
            'payables',
            'pending_sales',
            'payment_transfers',
            'customer_needs',
            'agent_credits',
            'agent_assignments',
            'subadmin_roles',
            'settings',
        ], fn (string $table) => Schema::hasTable($table)));
    }

    public function up(): void
    {
        foreach ($this->operationalTables() as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasTable('tenants')) {
                    $blueprint->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
                } else {
                    $blueprint->unsignedBigInteger('tenant_id')->nullable()->after('id');
                }

                $blueprint->index('tenant_id');
            });
        }

        if (! Schema::hasTable('tenants')) {
            return;
        }

        $defaultTenantId = DB::table('tenants')->orderBy('id')->value('id');
        if ($defaultTenantId === null) {
            return;
        }

        $platformSettingKeys = [
            'selcom_vendor_id',
            'selcom_api_key',
            'selcom_api_secret',
            'selcom_is_live',
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
            'vendor_subscription_payment_mode',
        ];

        foreach ($this->operationalTables() as $table) {
            if ($table === 'settings') {
                DB::table('settings')
                    ->whereNull('tenant_id')
                    ->whereNotIn('key', $platformSettingKeys)
                    ->update(['tenant_id' => $defaultTenantId]);

                continue;
            }

            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->operationalTables()) as $table) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                try {
                    $blueprint->dropConstrainedForeignId('tenant_id');
                } catch (\Throwable) {
                    $blueprint->dropColumn('tenant_id');
                }
            });
        }
    }
};
