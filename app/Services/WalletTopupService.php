<?php

namespace App\Services;

use App\Models\Selcompay;
use App\Models\WalletTransaction;
use App\Support\PlatformPaymentSettings;
use App\Support\TanzaniaMobileNumber;
use Illuminate\Support\Facades\Log;

/**
 * Money IN to a vendor's disbursement wallet via the Selcom Checkout (collection)
 * API — the mirror of {@see SelcomBusinessDisbursementService} (money out). Funds
 * collected here credit the internal wallet balance the payout flow draws from.
 *
 * Modeled on {@see VendorSubscriptionPaymentService}: create order → wallet push →
 * poll/webhook → on COMPLETED, credit the wallet (idempotently).
 */
class WalletTopupService
{
    public function __construct(
        protected SelcomCredentialResolver $credentials,
        protected WalletService $wallet,
    ) {
    }

    public function isDemoMode(): bool
    {
        return PlatformPaymentSettings::isVendorSubscriptionDemo();
    }

    /**
     * Start a top-up. In demo mode it completes and credits immediately.
     *
     * @param  array{email?: string, name?: string}  $buyer
     * @return array{ok: bool, message: string, selcompay: ?Selcompay, status: string}
     */
    public function initiate(int $tenantId, int $amount, string $phone, array $buyer = [], ?int $actorId = null): array
    {
        if ($amount < 500) {
            return ['ok' => false, 'message' => 'Minimum top-up is 500 TZS.', 'selcompay' => null, 'status' => 'error'];
        }

        try {
            $cleanPhone = TanzaniaMobileNumber::normalize($phone);
        } catch (\InvalidArgumentException) {
            return ['ok' => false, 'message' => 'Enter a valid mobile number (07… / 06… or 2557… / 2556…).', 'selcompay' => null, 'status' => 'error'];
        }

        if ($this->isDemoMode()) {
            return $this->initiateDemo($tenantId, $amount, $cleanPhone, $actorId);
        }

        if (! $this->credentials->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Selcom is not configured for live payments. Ask the platform team, or use demo mode.',
                'selcompay' => null,
                'status' => 'error',
            ];
        }

        $transid = 'WTOPUP_' . $tenantId . '_' . time();
        $orderId = 'WTOP' . now()->timestamp . random_int(1000, 9999);

        $creds = $this->credentials->resolve();
        $selcom = new SelcomApiService($creds['vendor'], $creds['api_key'], $creds['api_secret'], $creds['live']);

        $createPayload = [
            'vendor' => $creds['vendor'],
            'order_id' => $orderId,
            'buyer_email' => $buyer['email'] ?? 'wallet@opticedgeafrica.net',
            'buyer_name' => $buyer['name'] ?? 'Vendor wallet top-up',
            'buyer_phone' => $cleanPhone,
            'amount' => $amount,
            'currency' => 'TZS',
            'redirect_url' => base64_encode(route('admin.payout.index')),
            'cancel_url' => base64_encode(route('admin.payout.index')),
            'webhook' => base64_encode(route('selcom.checkout-callback')),
            'buyer_remarks' => 'Disbursement wallet top-up',
            'merchant_remarks' => 'Wallet top-up vendor #' . $tenantId,
            'no_of_items' => 1,
            'expiry' => (int) (config('selcom.expiry') ?? 60),
        ];

        $createResponse = $selcom->createOrderMinimal($createPayload);
        if (isset($createResponse['resultcode']) && $createResponse['resultcode'] !== '000') {
            return [
                'ok' => false,
                'message' => $createResponse['message'] ?? $createResponse['result'] ?? 'Payment gateway error.',
                'selcompay' => null,
                'status' => 'error',
            ];
        }

        $walletResponse = $selcom->walletPayment($transid, $orderId, $cleanPhone);
        if (isset($walletResponse['resultcode']) && ! in_array($walletResponse['resultcode'], ['000', '111'], true)) {
            return [
                'ok' => false,
                'message' => $walletResponse['message'] ?? $walletResponse['result'] ?? 'Payment request failed.',
                'selcompay' => null,
                'status' => 'error',
            ];
        }

        $selcompay = Selcompay::create([
            'transid' => $transid,
            'order_id' => $orderId,
            'phone_number' => $cleanPhone,
            'amount' => $amount,
            'payment_status' => 'pending',
            'local_order_id' => null,
            'purpose' => Selcompay::PURPOSE_WALLET_TOPUP,
            'payout_source_type' => 'tenant',
            'payout_source_id' => $tenantId,
        ]);

        return [
            'ok' => true,
            'message' => 'Check your phone to approve the top-up.',
            'selcompay' => $selcompay,
            'status' => 'pending',
        ];
    }

    protected function initiateDemo(int $tenantId, int $amount, string $phone, ?int $actorId): array
    {
        $selcompay = Selcompay::create([
            'transid' => 'DEMO_WTOPUP_' . $tenantId . '_' . time(),
            'order_id' => 'DEMOWTOP' . now()->timestamp . random_int(1000, 9999),
            'phone_number' => $phone,
            'amount' => $amount,
            'payment_status' => 'completed',
            'local_order_id' => null,
            'purpose' => Selcompay::PURPOSE_WALLET_TOPUP,
            'payout_source_type' => 'tenant',
            'payout_source_id' => $tenantId,
        ]);

        $this->creditFromSelcompay($selcompay, $actorId);

        return [
            'ok' => true,
            'message' => 'Demo top-up of ' . number_format($amount) . ' TZS added to your wallet.',
            'selcompay' => $selcompay,
            'status' => 'completed',
        ];
    }

    /**
     * Poll Selcom for a pending top-up and credit the wallet once completed.
     *
     * @return array{status: string, message: string}
     */
    public function checkStatus(Selcompay $selcompay): array
    {
        if ($selcompay->purpose !== Selcompay::PURPOSE_WALLET_TOPUP) {
            return ['status' => 'error', 'message' => 'Not a wallet top-up record.'];
        }

        if ($selcompay->payment_status === 'completed') {
            $this->creditFromSelcompay($selcompay);

            return ['status' => 'completed', 'message' => 'Top-up complete. Wallet credited.'];
        }

        if (in_array($selcompay->payment_status, ['failed', 'cancelled', 'rejected', 'usercancelled'], true)) {
            return ['status' => 'failed', 'message' => 'Top-up ' . $selcompay->payment_status . '.'];
        }

        if (! $selcompay->order_id || ! $this->credentials->isConfigured()) {
            return ['status' => 'pending', 'message' => 'Waiting for payment confirmation…'];
        }

        $creds = $this->credentials->resolve();
        $selcom = new SelcomApiService($creds['vendor'], $creds['api_key'], $creds['api_secret'], $creds['live']);
        $statusArr = $selcom->orderStatus($selcompay->order_id);

        if (! isset($statusArr['resultcode']) || $statusArr['resultcode'] !== '000') {
            return ['status' => 'pending', 'message' => $statusArr['message'] ?? 'Unable to verify payment yet.'];
        }

        $paymentStatus = $statusArr['data'][0]['payment_status'] ?? null;

        if ($paymentStatus === 'COMPLETED') {
            $selcompay->update(['payment_status' => 'completed']);
            $this->creditFromSelcompay($selcompay);

            return ['status' => 'completed', 'message' => 'Top-up complete. Wallet credited.'];
        }

        if (in_array($paymentStatus, ['FAILED', 'CANCELLED', 'EXPIRED', 'REJECTED', 'USERCANCELLED'], true)) {
            $selcompay->update(['payment_status' => 'failed']);

            return ['status' => 'failed', 'message' => 'Top-up ' . strtolower((string) $paymentStatus) . '.'];
        }

        return ['status' => 'pending', 'message' => 'Payment is being processed…'];
    }

    /**
     * Credit the wallet for a completed top-up. Idempotent per Selcompay.
     */
    public function creditFromSelcompay(Selcompay $selcompay, ?int $actorId = null): void
    {
        if ($selcompay->purpose !== Selcompay::PURPOSE_WALLET_TOPUP || $selcompay->payment_status !== 'completed') {
            return;
        }

        if ($selcompay->payout_source_type !== 'tenant' || ! $selcompay->payout_source_id) {
            Log::warning('Wallet top-up missing tenant reference', ['selcompay_id' => $selcompay->id]);

            return;
        }

        $this->wallet->credit(
            (int) $selcompay->payout_source_id,
            (float) $selcompay->amount,
            WalletTransaction::TYPE_TOPUP,
            'selcompay',
            (int) $selcompay->id,
            'Selcom wallet top-up',
            $actorId,
        );
    }
}
