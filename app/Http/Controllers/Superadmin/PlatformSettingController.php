<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SelcomPaymentTestService;
use App\Services\VendorSubscriptionPaymentService;
use App\Support\PlatformPaymentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingController extends Controller
{
    public const PAYMENT_KEYS = [
        PlatformPaymentSettings::KEY_VENDOR_SUBSCRIPTION_MODE,
    ];

    public const SELCOM_KEYS = [
        'selcom_vendor_id',
        'selcom_api_key',
        'selcom_api_secret',
        'selcom_is_live',
    ];

    public const MAIL_KEYS = [
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
    ];

    public const AUTH_KEYS = [
        \App\Support\PlatformAuthSettings::KEY_REQUIRE_EMAIL_VERIFICATION,
    ];

    public function index(): View
    {
        $settings = Setting::query()
            ->whereNull('tenant_id')
            ->whereIn('key', array_merge(self::PAYMENT_KEYS, self::SELCOM_KEYS, self::MAIL_KEYS, self::AUTH_KEYS))
            ->pluck('value', 'key');

        return view('superadmin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_subscription_payment_mode' => 'required|in:demo,live',
            'selcom_vendor_id' => 'nullable|string|max:255',
            'selcom_api_key' => 'nullable|string|max:255',
            'selcom_api_secret' => 'nullable|string|max:255',
            'selcom_is_live' => 'nullable|in:0,1',
            'mail_mailer' => 'nullable|string|max:100',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|max:50',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'require_email_verification_on_login' => 'nullable|in:0,1',
        ]);

        if (! array_key_exists('require_email_verification_on_login', $data)) {
            $data['require_email_verification_on_login'] = '0';
        }

        foreach ($data as $key => $value) {
            Setting::query()->withoutGlobalScopes()->updateOrCreate(
                ['key' => $key, 'tenant_id' => null],
                ['value' => $value === null ? null : (string) $value]
            );
        }

        return redirect()
            ->route('superadmin.settings.index')
            ->with('success', 'Platform settings saved successfully.');
    }

    public function testSelcom(VendorSubscriptionPaymentService $payments): JsonResponse
    {
        $result = $payments->testSelcomConnection();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function testSelcomMobile(Request $request, SelcomPaymentTestService $tester): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'amount' => 'nullable|integer|min:100|max:1000000',
        ]);

        $result = $tester->testMobileMoney($data['phone'], (int) ($data['amount'] ?? 1000));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function testSelcomCard(Request $request, SelcomPaymentTestService $tester): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'nullable|integer|min:100|max:1000000',
        ]);

        $result = $tester->testCard((int) ($data['amount'] ?? 1000));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function testSelcomStatus(Request $request, SelcomPaymentTestService $tester): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|string|max:100',
        ]);

        $result = $tester->checkOrder($data['order_id']);

        return response()->json($result, 200);
    }
}
