<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Superadmin\PlatformSettingController;
use App\Models\Setting;
use App\Services\VendorSubscriptionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperadminPlatformSettingApiController extends Controller
{
    public function index(): JsonResponse
    {
        $keys = array_merge(
            PlatformSettingController::PAYMENT_KEYS,
            PlatformSettingController::SELCOM_KEYS,
            PlatformSettingController::MAIL_KEYS,
        );

        $settings = Setting::query()
            ->whereNull('tenant_id')
            ->whereIn('key', $keys)
            ->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
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
        ]);

        foreach ($data as $key => $value) {
            Setting::query()->withoutGlobalScopes()->updateOrCreate(
                ['key' => $key, 'tenant_id' => null],
                ['value' => $value === null ? null : (string) $value]
            );
        }

        return response()->json(['message' => 'Platform settings saved successfully.']);
    }

    public function testSelcom(VendorSubscriptionPaymentService $payments): JsonResponse
    {
        $result = $payments->testSelcomConnection();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
