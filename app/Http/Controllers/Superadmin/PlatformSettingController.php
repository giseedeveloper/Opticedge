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

    /** Selcom Business (disbursement) API — used for LIVE agent commission payouts. */
    public const SELCOM_BUSINESS_KEYS = [
        'selcom_biz_api_key',
        'selcom_biz_private_key_path',
        'selcom_biz_is_live',
        'selcom_biz_account_number',
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

    public const AGENT_SUBSCRIPTION_KEYS = [
        \App\Support\PlatformAgentSubscriptionSettings::KEY_ENABLED,
        \App\Support\PlatformAgentSubscriptionSettings::KEY_MONTHLY_AMOUNT,
    ];

    public function index(): View
    {
        $settings = Setting::query()
            ->whereNull('tenant_id')
            ->whereIn('key', array_merge(
                self::PAYMENT_KEYS,
                self::SELCOM_KEYS,
                self::SELCOM_BUSINESS_KEYS,
                self::MAIL_KEYS,
                self::AUTH_KEYS,
                self::AGENT_SUBSCRIPTION_KEYS,
            ))
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
            'selcom_biz_api_key' => 'nullable|string|max:255',
            'selcom_biz_private_key_path' => 'nullable|string|max:1024',
            'selcom_biz_is_live' => 'nullable|in:0,1',
            'selcom_biz_account_number' => 'nullable|string|max:64',
            'mail_mailer' => 'nullable|string|max:100',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|max:50',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'require_email_verification_on_login' => 'nullable|in:0,1',
            'agent_subscription_enabled' => 'nullable|in:0,1',
            'agent_subscription_monthly_amount' => 'nullable|numeric|min:0',
        ]);

        if (! array_key_exists('require_email_verification_on_login', $data)) {
            $data['require_email_verification_on_login'] = '0';
        }

        if (! array_key_exists('agent_subscription_enabled', $data)) {
            $data['agent_subscription_enabled'] = '0';
        }

        if (! array_key_exists('agent_subscription_monthly_amount', $data) || $data['agent_subscription_monthly_amount'] === null) {
            $data['agent_subscription_monthly_amount'] = '0';
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

    /**
     * Send a one-off TEST disbursement via the Selcom Business API. The environment
     * (sandbox/live) is chosen per-test and overrides the saved setting, so live
     * moves real money — the UI warns before sending.
     */
    public function testBusinessDisburse(Request $request, \App\Services\SelcomBusinessCredentialResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'amount' => 'required|integer|min:1|max:1000000',
            'name' => 'nullable|string|max:100',
            'env' => 'required|in:sandbox,live',
        ]);

        $creds = $resolver->resolve();
        if (trim($creds['api_key']) === '') {
            return response()->json(['ok' => false, 'message' => 'Save the Business API key first.'], 200);
        }
        if (trim($creds['private_key_path']) === '' || ! is_file($creds['private_key_path']) || ! is_readable($creds['private_key_path'])) {
            return response()->json(['ok' => false, 'message' => 'Business private key (.pem) is missing or unreadable on the server.'], 200);
        }

        try {
            $msisdn = \App\Support\TanzaniaMobileNumber::normalize($data['phone']);
        } catch (\InvalidArgumentException) {
            return response()->json(['ok' => false, 'message' => 'Invalid mobile number (use 07… / 06… or 2557… / 2556…).'], 200);
        }

        $fiCode = \App\Services\SelcomBusinessApiService::fiCodeForMsisdn($msisdn);
        if ($fiCode === null) {
            return response()->json(['ok' => false, 'message' => 'This mobile network is not supported for disbursement.'], 200);
        }

        $isLive = $data['env'] === 'live';
        $transId = 'TEST_DISB_' . now()->timestamp . '_' . random_int(100, 999);

        $payload = [
            'transId' => $transId,
            'recipientFiCode' => $fiCode,
            'recipientAccount' => $msisdn,
            'recipientName' => mb_substr(trim((string) ($data['name'] ?? '')) ?: 'Test recipient', 0, 100),
            'amount' => (int) $data['amount'],
            'purpose' => (string) config('selcom_business.purpose_code', 'BUSINESS_EXPENSES'),
            'remarks' => 'Test disbursement',
        ];

        try {
            $api = new \App\Services\SelcomBusinessApiService($creds['api_key'], $creds['private_key_path'], $isLive);
            $response = $api->processTransaction($payload);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Could not reach Selcom Business: ' . $e->getMessage()], 200);
        }

        [$ok, $message] = $this->interpretBusinessResponse($response, 'Test disbursement');

        return response()->json([
            'ok' => $ok,
            'message' => $message,
            'env' => $isLive ? 'live' : 'sandbox',
            'trans_id' => $transId,
            'receipt' => $response['data']['selcom_receipt'] ?? null,
            'status' => $response['data']['status'] ?? null,
        ], 200);
    }

    /**
     * Query the status of a previously-sent test disbursement.
     */
    public function testBusinessDisburseStatus(Request $request, \App\Services\SelcomBusinessCredentialResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'trans_id' => 'required|string|max:100',
            'env' => 'required|in:sandbox,live',
        ]);

        $creds = $resolver->resolve();
        if (trim($creds['api_key']) === '') {
            return response()->json(['ok' => false, 'message' => 'Save the Business API key first.'], 200);
        }

        try {
            $api = new \App\Services\SelcomBusinessApiService($creds['api_key'], $creds['private_key_path'], $data['env'] === 'live');
            $response = $api->queryTransaction($data['trans_id']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Could not reach Selcom Business: ' . $e->getMessage()], 200);
        }

        [$ok, $message] = $this->interpretBusinessResponse($response, 'Transaction');

        return response()->json([
            'ok' => $ok,
            'message' => $message,
            'status' => $response['data']['status'] ?? null,
        ], 200);
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function interpretBusinessResponse(array $response, string $label): array
    {
        $resultcode = isset($response['resultcode']) ? (string) $response['resultcode'] : null;
        $status = strtoupper((string) ($response['data']['status'] ?? ''));
        $apiMsg = trim((string) ($response['message'] ?? $response['result'] ?? ''));

        if ($resultcode === '000' || $status === 'COMPLETED') {
            return [true, $label . ' completed.' . ($apiMsg !== '' ? ' (' . $apiMsg . ')' : '')];
        }

        if ($resultcode === '111' || $status === 'ACCEPTED') {
            return [true, $label . ' submitted; Selcom is processing. Use “Check status”.'];
        }

        return [false, $apiMsg !== '' ? $apiMsg : ($label . ' failed.')];
    }
}
