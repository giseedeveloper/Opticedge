<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

/**
 * Resolves Selcom API credentials from Admin → Settings, falling back to config (.env).
 */
class SelcomCredentialResolver
{
    /**
     * @return array{vendor: string, api_key: string, api_secret: string, live: bool}
     */
    public function resolve(): array
    {
        $settings = Setting::query()
            ->withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->whereIn('key', [
            'selcom_vendor_id', 'selcom_api_key', 'selcom_api_secret', 'selcom_is_live',
        ])->pluck('value', 'key');

        $vendor = trim((string) ($settings->get('selcom_vendor_id') ?? ''));
        $key = trim((string) ($settings->get('selcom_api_key') ?? ''));
        $secret = trim((string) ($settings->get('selcom_api_secret') ?? ''));
        $isLive = in_array($settings->get('selcom_is_live'), ['1', 'true', 'yes'], true);

        if ($vendor !== '' && $key !== '' && $secret !== '') {
            return [
                'vendor' => $vendor,
                'api_key' => $key,
                'api_secret' => $secret,
                'live' => $isLive,
            ];
        }

        return [
            'vendor' => $vendor !== '' ? $vendor : (string) Config::get('selcom.vendor'),
            'api_key' => $key !== '' ? $key : (string) Config::get('selcom.key'),
            'api_secret' => $secret !== '' ? $secret : (string) Config::get('selcom.secret'),
            'live' => $isLive ?: (bool) Config::get('selcom.live'),
        ];
    }

    public function isConfigured(): bool
    {
        $creds = $this->resolve();

        return trim($creds['vendor']) !== ''
            && trim($creds['api_key']) !== ''
            && trim($creds['api_secret']) !== '';
    }
}
