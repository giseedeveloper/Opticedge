<?php

namespace App\Support;

use App\Models\Setting;

class PlatformPaymentSettings
{
    public const MODE_DEMO = 'demo';

    public const MODE_LIVE = 'live';

    public const KEY_VENDOR_SUBSCRIPTION_MODE = 'vendor_subscription_payment_mode';

    public static function vendorSubscriptionPaymentMode(): string
    {
        $mode = Setting::query()
            ->withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('key', self::KEY_VENDOR_SUBSCRIPTION_MODE)
            ->value('value');

        return in_array($mode, [self::MODE_DEMO, self::MODE_LIVE], true)
            ? $mode
            : self::MODE_DEMO;
    }

    public static function isVendorSubscriptionDemo(): bool
    {
        return self::vendorSubscriptionPaymentMode() === self::MODE_DEMO;
    }

    public static function isVendorSubscriptionLive(): bool
    {
        return self::vendorSubscriptionPaymentMode() === self::MODE_LIVE;
    }
}
