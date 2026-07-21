<?php

namespace App\Support;

use App\Models\Setting;

class PlatformAgentSubscriptionSettings
{
    public const KEY_ENABLED = 'agent_subscription_enabled';

    public const KEY_MONTHLY_AMOUNT = 'agent_subscription_monthly_amount';

    public static function isEnabled(): bool
    {
        $value = Setting::query()
            ->withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('key', self::KEY_ENABLED)
            ->value('value');

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    public static function monthlyAmount(): float
    {
        $value = Setting::query()
            ->withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('key', self::KEY_MONTHLY_AMOUNT)
            ->value('value');

        return max(0, (float) $value);
    }
}
