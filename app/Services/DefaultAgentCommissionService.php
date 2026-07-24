<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant default commission amount applied automatically to each agent sale / credit.
 */
class DefaultAgentCommissionService
{
    public const SETTING_KEY = 'default_commission_amount';

    private const EPS = 0.0001;

    public function getAmount(?int $tenantId = null): float
    {
        $raw = Setting::query()->where('key', self::SETTING_KEY)->value('value');

        return max(0, (float) ($raw ?? 0));
    }

    public function setAmount(float $amount, ?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? (int) (auth()->user()->tenant_id ?? TenantContext::id() ?? 0);
        $amount = max(0, round($amount, 2));

        Setting::query()->withoutGlobalScopes()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'value' => (string) $amount,
                'tenant_id' => $tenantId > 0 ? $tenantId : null,
            ]
        );
    }

    /**
     * Commission for a sale/credit line based on quantity of devices.
     */
    public function amountForQuantity(int $quantity = 1, ?int $tenantId = null): float
    {
        $qty = max(1, $quantity);
        $per = $this->getAmount($tenantId);

        if ($per <= self::EPS) {
            return 0.0;
        }

        return round($per * $qty, 2);
    }

    /**
     * Merge commission_paid into create attrs when the column exists.
     *
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    public function applyToCreateAttrs(array $attrs, string $table, int $quantity = 1, ?int $tenantId = null): array
    {
        if (! Schema::hasColumn($table, 'commission_paid')) {
            return $attrs;
        }

        if (! array_key_exists('commission_paid', $attrs) || $attrs['commission_paid'] === null) {
            $attrs['commission_paid'] = $this->amountForQuantity($quantity, $tenantId);
        }

        return $attrs;
    }

    /**
     * True when Selcom Business has completed a disbursement for this sale/credit line.
     */
    public function lineIsDisbursed(string $source, int $id): bool
    {
        $dbType = $source === 'credit' ? 'agent_credit' : 'agent_sale';
        if (! Schema::hasTable('selcompays') || ! Schema::hasColumn('selcompays', 'purpose')) {
            return false;
        }

        return \App\Models\Selcompay::query()
            ->where('purpose', \App\Models\Selcompay::PURPOSE_AGENT_COMMISSION_DISBURSE)
            ->where('payment_status', 'completed')
            ->where('payout_source_type', $dbType)
            ->where('payout_source_id', $id)
            ->exists();
    }
}
