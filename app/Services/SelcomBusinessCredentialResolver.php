<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

/**
 * Resolves Selcom Business (disbursement) credentials from Admin → Settings,
 * falling back to config (.env). Distinct from {@see SelcomCredentialResolver},
 * which serves the Selcom Checkout (collection) API.
 */
class SelcomBusinessCredentialResolver
{
    /**
     * @return array{api_key: string, private_key_path: string, live: bool, account_number: string}
     */
    public function resolve(): array
    {
        $settings = Setting::query()
            ->withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->whereIn('key', [
                'selcom_biz_api_key',
                'selcom_biz_private_key_path',
                'selcom_biz_is_live',
                'selcom_biz_account_number',
            ])
            ->pluck('value', 'key');

        $apiKey = trim((string) ($settings->get('selcom_biz_api_key') ?? ''));
        $keyPath = trim((string) ($settings->get('selcom_biz_private_key_path') ?? ''));
        $isLive = in_array($settings->get('selcom_biz_is_live'), ['1', 'true', 'yes'], true);
        $account = trim((string) ($settings->get('selcom_biz_account_number') ?? ''));

        return [
            'api_key' => $apiKey !== '' ? $apiKey : (string) Config::get('selcom_business.api_key'),
            'private_key_path' => $keyPath !== '' ? $keyPath : (string) Config::get('selcom_business.private_key_path'),
            'live' => $isLive ?: (bool) Config::get('selcom_business.live'),
            'account_number' => $account !== '' ? $account : (string) Config::get('selcom_business.account_number'),
        ];
    }

    public function isConfigured(): bool
    {
        $creds = $this->resolve();

        return trim($creds['api_key']) !== ''
            && trim($creds['private_key_path']) !== ''
            && is_file($creds['private_key_path'])
            && is_readable($creds['private_key_path']);
    }
}
