<?php

namespace App\Services;

use App\Models\Selcompay;
use App\Models\VendorRegistrationIntent;
use App\Support\PlatformPaymentSettings;
use App\Support\TanzaniaMobileNumber;
use App\Services\VendorRegistrationFulfillmentService;
use Illuminate\Support\Facades\Log;

class VendorSubscriptionPaymentService
{
    public function __construct(
        protected SelcomCredentialResolver $credentials,
        protected VendorRegistrationFulfillmentService $fulfillment,
    ) {}

    public function isDemoMode(): bool
    {
        return PlatformPaymentSettings::isVendorSubscriptionDemo();
    }

    /**
     * @return array{ok: bool, message: string, details?: array<string, mixed>}
     */
    public function testSelcomConnection(): array
    {
        if (! $this->credentials->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Selcom credentials are missing. Fill Vendor ID, API key, and API secret first.',
            ];
        }

        $creds = $this->credentials->resolve();
        $selcom = new SelcomApiService(
            $creds['vendor'],
            $creds['api_key'],
            $creds['api_secret'],
            $creds['live']
        );

        $probeOrderId = 'CONN_TEST_'.now()->timestamp;
        $response = $selcom->orderStatus($probeOrderId);

        if (isset($response['resultcode']) && $response['resultcode'] === 'CONNECTION_ERROR') {
            return [
                'ok' => false,
                'message' => $response['message'] ?? 'Could not reach Selcom API.',
                'details' => $response,
            ];
        }

        $gatewayMessage = $response['message'] ?? $response['result'] ?? 'Selcom API responded.';
        $environment = $creds['live'] ? 'Live (apigw.selcommobile.com)' : 'Test (apigwtest.selcommobile.com)';

        return [
            'ok' => true,
            'message' => "Connected to Selcom ({$environment}). {$gatewayMessage}",
            'details' => $response,
        ];
    }

    public function initiatePayment(VendorRegistrationIntent $intent, string $paymentPhone): void
    {
        if ($this->isDemoMode()) {
            $this->initiateDemoPayment($intent, $paymentPhone);

            return;
        }

        if (! $this->credentials->isConfigured()) {
            throw new \RuntimeException(
                'Live payments require Selcom API credentials. Configure them below or switch vendor signup to Demo mode.'
            );
        }

        $package = $intent->package;
        $cleanPhone = $this->normalizePhone($paymentPhone);

        $transid = 'VENDOR_'.$intent->id.'_'.time();
        $orderId = 'VSUB'.now()->timestamp.rand(1000, 9999);

        $creds = $this->credentials->resolve();
        $selcom = new SelcomApiService(
            $creds['vendor'],
            $creds['api_key'],
            $creds['api_secret'],
            $creds['live']
        );

        $amount = (int) round((float) $package->price);
        $redirectUrl = route('vendor.subscribe.success', $intent);
        $cancelUrl = route('vendor.subscribe', $package);

        $createPayload = [
            'vendor' => $creds['vendor'],
            'order_id' => $orderId,
            'buyer_email' => $intent->email,
            'buyer_name' => $intent->admin_name,
            'buyer_phone' => $cleanPhone,
            'amount' => $amount,
            'currency' => 'TZS',
            'redirect_url' => base64_encode($redirectUrl),
            'cancel_url' => base64_encode($cancelUrl),
            'webhook' => base64_encode(route('selcom.checkout-callback')),
            'buyer_remarks' => 'Vendor subscription '.$package->name,
            'merchant_remarks' => 'Vendor #'.$intent->id,
            'no_of_items' => 1,
            'expiry' => (int) (config('selcom.expiry') ?? 60),
        ];

        $createResponse = $selcom->createOrderMinimal($createPayload);

        if (isset($createResponse['resultcode']) && $createResponse['resultcode'] !== '000') {
            $message = $createResponse['message'] ?? $createResponse['result'] ?? 'Payment gateway error';
            throw new \RuntimeException($message);
        }

        $walletResponse = $selcom->walletPayment($transid, $orderId, $cleanPhone);

        if (isset($walletResponse['resultcode']) && ! in_array($walletResponse['resultcode'], ['000', '111'], true)) {
            $message = $walletResponse['message'] ?? $walletResponse['result'] ?? 'Payment request failed';
            throw new \RuntimeException($message);
        }

        Selcompay::create([
            'transid' => $transid,
            'order_id' => $orderId,
            'phone_number' => $cleanPhone,
            'amount' => $amount,
            'payment_status' => 'pending',
            'local_order_id' => null,
            'purpose' => Selcompay::PURPOSE_VENDOR_SUBSCRIPTION,
            'payout_source_type' => 'vendor_registration_intent',
            'payout_source_id' => $intent->id,
        ]);

        $intent->update([
            'status' => VendorRegistrationIntent::STATUS_PAYMENT_PENDING,
            'payment_phone' => $cleanPhone,
        ]);
    }

    protected function initiateDemoPayment(VendorRegistrationIntent $intent, string $paymentPhone): void
    {
        $package = $intent->package;
        $cleanPhone = $this->normalizePhone($paymentPhone);
        $amount = (int) round((float) $package->price);
        $transid = 'DEMO_VENDOR_'.$intent->id.'_'.time();
        $orderId = 'DEMOVSUB'.now()->timestamp.rand(1000, 9999);

        Selcompay::create([
            'transid' => $transid,
            'order_id' => $orderId,
            'phone_number' => $cleanPhone,
            'amount' => $amount,
            'payment_status' => 'completed',
            'local_order_id' => null,
            'purpose' => Selcompay::PURPOSE_VENDOR_SUBSCRIPTION,
            'payout_source_type' => 'vendor_registration_intent',
            'payout_source_id' => $intent->id,
        ]);

        $intent->update([
            'status' => VendorRegistrationIntent::STATUS_PAYMENT_PENDING,
            'payment_phone' => $cleanPhone,
        ]);

        try {
            $this->fulfillment->fulfill($intent->fresh());
        } catch (\Throwable $e) {
            Log::error('Demo vendor subscription fulfillment failed', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Demo payment recorded but account activation failed: '.$this->friendlyFulfillmentError($e)
            );
        }
    }

    public function checkAndFulfill(VendorRegistrationIntent $intent): array
    {
        $selcompay = Selcompay::query()
            ->where('purpose', Selcompay::PURPOSE_VENDOR_SUBSCRIPTION)
            ->where('payout_source_type', 'vendor_registration_intent')
            ->where('payout_source_id', $intent->id)
            ->latest()
            ->first();

        if (! $selcompay) {
            return ['status' => 'error', 'message' => 'No payment record found.'];
        }

        if ($intent->isCompleted()) {
            return ['status' => 'completed', 'message' => 'Subscription active.'];
        }

        if ($selcompay->payment_status === 'completed') {
            try {
                $this->fulfillment->fulfill($intent->fresh());
            } catch (\Throwable $e) {
                Log::error('Vendor subscription fulfillment failed', [
                    'intent_id' => $intent->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'status' => 'error',
                    'message' => 'Payment received but activation failed: '.$this->friendlyFulfillmentError($e),
                ];
            }

            return [
                'status' => 'completed',
                'message' => $this->isDemoMode()
                    ? 'Demo payment successful — your vendor account is being activated.'
                    : 'Payment successful!',
            ];
        }

        if ($this->isDemoMode()) {
            return ['status' => 'pending', 'message' => 'Activating your demo subscription…'];
        }

        if (! $selcompay->order_id) {
            return ['status' => 'pending', 'message' => 'Waiting for payment confirmation...'];
        }

        if (! $this->credentials->isConfigured()) {
            return ['status' => 'error', 'message' => 'Selcom is not configured for live payments.'];
        }

        $creds = $this->credentials->resolve();
        $selcom = new SelcomApiService(
            $creds['vendor'],
            $creds['api_key'],
            $creds['api_secret'],
            $creds['live']
        );

        $statusArr = $selcom->orderStatus($selcompay->order_id);

        if (! isset($statusArr['resultcode']) || $statusArr['resultcode'] !== '000') {
            $errorMessage = $statusArr['message'] ?? $statusArr['result'] ?? 'Unable to verify payment';

            return ['status' => 'error', 'message' => $errorMessage];
        }

        $paymentStatus = $statusArr['data'][0]['payment_status'] ?? null;

        if ($paymentStatus === 'COMPLETED') {
            $selcompay->update(['payment_status' => 'completed']);

            try {
                $this->fulfillment->fulfill($intent->fresh());
            } catch (\Throwable $e) {
                Log::error('Vendor subscription fulfillment failed after Selcom completion', [
                    'intent_id' => $intent->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'status' => 'error',
                    'message' => 'Payment received but activation failed: '.$this->friendlyFulfillmentError($e),
                ];
            }

            return ['status' => 'completed', 'message' => 'Payment successful!'];
        }

        if (in_array($paymentStatus, ['FAILED', 'CANCELLED', 'EXPIRED', 'REJECTED', 'USERCANCELLED'], true)) {
            $selcompay->update(['payment_status' => 'failed']);
            $intent->update(['status' => VendorRegistrationIntent::STATUS_FAILED]);

            return ['status' => 'failed', 'message' => 'Payment '.strtolower((string) $paymentStatus).'. Please try again.'];
        }

        return ['status' => 'pending', 'message' => 'Payment is being processed...'];
    }

    public function handleWebhookCompleted(Selcompay $selcompay): void
    {
        if ($selcompay->payout_source_type !== 'vendor_registration_intent' || ! $selcompay->payout_source_id) {
            return;
        }

        $intent = VendorRegistrationIntent::find($selcompay->payout_source_id);
        if (! $intent || $intent->isCompleted()) {
            return;
        }

        try {
            $this->fulfillment->fulfill($intent);
        } catch (\Throwable $e) {
            Log::error('Vendor registration fulfillment failed', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function normalizePhone(string $phone): string
    {
        return TanzaniaMobileNumber::normalize($phone);
    }

    protected function friendlyFulfillmentError(\Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'users_email_unique') || (str_contains($message, 'Duplicate entry') && str_contains($message, 'email'))) {
            return 'This email is already registered. Sign in or use a different email.';
        }

        if (str_contains($message, 'users_phone_unique') || (str_contains($message, 'Duplicate entry') && str_contains($message, 'phone'))) {
            return 'This phone number is already registered.';
        }

        return 'Please contact support or try again with different details.';
    }
}
