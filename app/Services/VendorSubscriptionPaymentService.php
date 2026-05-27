<?php

namespace App\Services;

use App\Models\Selcompay;
use App\Models\VendorRegistrationIntent;
use App\Services\VendorRegistrationFulfillmentService;
use Illuminate\Support\Facades\Log;

class VendorSubscriptionPaymentService
{
    public function __construct(
        protected SelcomCredentialResolver $credentials,
        protected VendorRegistrationFulfillmentService $fulfillment,
    ) {}

    public function initiatePayment(VendorRegistrationIntent $intent, string $paymentPhone): void
    {
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
            $this->fulfillment->fulfill($intent->fresh());

            return ['status' => 'completed', 'message' => 'Payment successful!'];
        }

        if (! $selcompay->order_id) {
            return ['status' => 'pending', 'message' => 'Waiting for payment confirmation...'];
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
            $this->fulfillment->fulfill($intent->fresh());

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
        $clean = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if (! preg_match('/^(255)?[67]\d{8}$/', $clean)) {
            throw new \InvalidArgumentException('Invalid phone number. Use format 07XXXXXXXX or 2557XXXXXXXX.');
        }

        if (strlen($clean) === 9) {
            $clean = '255'.$clean;
        }

        return $clean;
    }
}
