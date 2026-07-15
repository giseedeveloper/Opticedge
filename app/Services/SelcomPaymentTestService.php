<?php

namespace App\Services;

use App\Support\TanzaniaMobileNumber;
use Illuminate\Support\Facades\Log;

/**
 * Superadmin diagnostics for Selcom payment channels.
 *
 * Lets a superadmin confirm that mobile money (wallet push) and card payments
 * actually work with the currently configured Selcom credentials, without going
 * through a real order. No Selcompay records are created, so nothing is fulfilled;
 * the Selcom webhook simply ignores these throwaway order ids.
 */
class SelcomPaymentTestService
{
    public function __construct(
        protected SelcomCredentialResolver $credentials,
    ) {}

    /**
     * Send a real wallet USSD push to a phone number to confirm mobile money works.
     *
     * @return array{ok: bool, message: string, order_id?: string, details?: array<string, mixed>}
     */
    public function testMobileMoney(string $phone, int $amount): array
    {
        if (! $this->credentials->isConfigured()) {
            return $this->notConfigured();
        }

        try {
            $cleanPhone = TanzaniaMobileNumber::normalize($phone);
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $selcom = $this->makeService();
        $orderId = 'TESTM'.now()->timestamp.rand(1000, 9999);
        $transid = 'TESTM_'.now()->timestamp.'_'.rand(1000, 9999);

        $create = $selcom->createOrderMinimal($this->buildOrderPayload($orderId, $cleanPhone, $amount));
        if (($create['resultcode'] ?? null) !== '000') {
            return [
                'ok' => false,
                'message' => 'Order creation failed: '.$this->errorText($create),
                'details' => $create,
            ];
        }

        $wallet = $selcom->walletPayment($transid, $orderId, $cleanPhone);
        Log::info('Selcom mobile money diagnostic', ['order_id' => $orderId, 'phone' => substr($cleanPhone, -4), 'response' => $wallet]);

        $code = $wallet['resultcode'] ?? null;
        if (in_array($code, ['000', '111'], true)) {
            return [
                'ok' => true,
                'message' => "USSD prompt sent to {$cleanPhone}. Approve it on the phone to confirm mobile money works, then use \"Check status\".",
                'order_id' => $orderId,
                'details' => $wallet,
            ];
        }

        return [
            'ok' => false,
            'message' => 'Mobile money push failed: '.$this->errorText($wallet),
            'details' => $wallet,
        ];
    }

    /**
     * Create a test order and return the hosted Selcom checkout link where a card can be paid.
     *
     * @return array{ok: bool, message: string, order_id?: string, gateway_url?: string, details?: array<string, mixed>}
     */
    public function testCard(int $amount): array
    {
        if (! $this->credentials->isConfigured()) {
            return $this->notConfigured();
        }

        $selcom = $this->makeService();
        $orderId = 'TESTC'.now()->timestamp.rand(1000, 9999);

        // A buyer phone is required to create an order; card is chosen on the hosted page.
        $create = $selcom->createOrderMinimal($this->buildOrderPayload($orderId, '255700000000', $amount));
        if (($create['resultcode'] ?? null) !== '000') {
            return [
                'ok' => false,
                'message' => 'Order creation failed: '.$this->errorText($create),
                'details' => $create,
            ];
        }

        $gatewayUrl = $create['data'][0]['payment_gateway_url'] ?? null;
        if ($gatewayUrl && ! str_starts_with($gatewayUrl, 'http')) {
            $decoded = base64_decode($gatewayUrl, true);
            if ($decoded && str_starts_with($decoded, 'http')) {
                $gatewayUrl = $decoded;
            }
        }

        if (! $gatewayUrl) {
            return [
                'ok' => false,
                'message' => 'Order was created but Selcom did not return a checkout link. Card / bank payments may not be enabled on this account.',
                'details' => $create,
            ];
        }

        Log::info('Selcom card diagnostic', ['order_id' => $orderId]);

        return [
            'ok' => true,
            'message' => 'Test order created. Open the checkout link and pay by card or bank to confirm those channels work, then use "Check status".',
            'order_id' => $orderId,
            'gateway_url' => $gatewayUrl,
            'details' => $create,
        ];
    }

    /**
     * Look up the current status of a diagnostic test order.
     *
     * @return array{ok: bool, message: string, payment_status?: string, details?: array<string, mixed>}
     */
    public function checkOrder(string $orderId): array
    {
        if (! $this->credentials->isConfigured()) {
            return $this->notConfigured();
        }

        $status = $this->makeService()->orderStatus($orderId);

        if (($status['resultcode'] ?? null) !== '000') {
            return [
                'ok' => false,
                'message' => 'Unable to fetch status: '.$this->errorText($status),
                'details' => $status,
            ];
        }

        $paymentStatus = $status['data'][0]['payment_status'] ?? 'UNKNOWN';

        return [
            'ok' => $paymentStatus === 'COMPLETED',
            'message' => "Payment status: {$paymentStatus}",
            'payment_status' => $paymentStatus,
            'details' => $status,
        ];
    }

    /**
     * @return array{ok: false, message: string}
     */
    protected function notConfigured(): array
    {
        return [
            'ok' => false,
            'message' => 'Selcom credentials are missing. Fill Vendor ID, API key, and API secret first.',
        ];
    }

    protected function makeService(): SelcomApiService
    {
        $creds = $this->credentials->resolve();

        return new SelcomApiService(
            $creds['vendor'],
            $creds['api_key'],
            $creds['api_secret'],
            $creds['live'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildOrderPayload(string $orderId, string $phone, int $amount): array
    {
        $creds = $this->credentials->resolve();
        $settingsUrl = route('superadmin.settings.index');

        return [
            'vendor' => $creds['vendor'],
            'order_id' => $orderId,
            'buyer_email' => 'diagnostics@opticedge.africa',
            'buyer_name' => 'Selcom Diagnostic',
            'buyer_phone' => $phone,
            'amount' => $amount,
            'currency' => 'TZS',
            'redirect_url' => base64_encode($settingsUrl),
            'cancel_url' => base64_encode($settingsUrl),
            'webhook' => base64_encode(route('selcom.checkout-callback')),
            'buyer_remarks' => 'Selcom payment diagnostic test',
            'merchant_remarks' => 'Superadmin diagnostic test',
            'no_of_items' => 1,
            'expiry' => (int) (config('selcom.expiry') ?? 60),
        ];
    }

    /**
     * @param array<string, mixed> $response
     */
    protected function errorText(array $response): string
    {
        return (string) ($response['message'] ?? $response['result'] ?? 'Unknown error from Selcom.');
    }
}
