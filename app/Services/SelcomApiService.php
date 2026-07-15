<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Selcom Checkout API client per official documentation.
 * @see https://developers.selcommobile.com/#checkout-api
 */
class SelcomApiService
{
    protected string $baseUrl;
    protected string $vendor;
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct(?string $vendor = null, ?string $apiKey = null, ?string $apiSecret = null, ?bool $live = null)
    {
        $this->vendor = $vendor ?? (string) config('selcom.vendor');
        $this->apiKey = $apiKey ?? (string) config('selcom.key');
        $this->apiSecret = $apiSecret ?? (string) config('selcom.secret');
        $isLive = $live ?? (bool) config('selcom.live');
        $subdomain = $isLive ? 'apigw' : 'apigwtest';
        $this->baseUrl = "https://{$subdomain}.selcommobile.com/v1";
    }

    /**
     * Build request headers per Selcom Authentication.
     * Digest = Base64(HMAC-SHA256(signed_data, secret)) where signed_data is timestamp=...&field1=value1&... in Signed-Fields order.
     */
    protected function buildHeaders(array $signedFields, array $data): array
    {
        $timestamp = now()->setTimezone('Africa/Dar_es_Salaam')->format('c');
        $parts = ["timestamp={$timestamp}"];
        foreach ($signedFields as $key) {
            $value = $data[$key] ?? '';
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $value = (string) $value;
            $parts[] = "{$key}={$value}";
        }
        $signedData = implode('&', $parts);
        $digest = base64_encode(hash_hmac('sha256', $signedData, $this->apiSecret, true));

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'SELCOM ' . base64_encode($this->apiKey),
            'Digest-Method' => 'HS256',
            'Digest' => $digest,
            'Timestamp' => $timestamp,
            'Signed-Fields' => implode(',', $signedFields),
        ];
    }

    /**
     * Create order (minimal) – for non-card payments (mobile wallet push).
     * POST /v1/checkout/create-order-minimal
     */
    public function createOrderMinimal(array $payload): array
    {
        $signedFields = array_keys($payload);
        $url = $this->baseUrl . '/checkout/create-order-minimal';
        $headers = $this->buildHeaders($signedFields, $payload);

        try {
            $response = Http::connectTimeout(30)
                ->timeout(90)
                ->retry(3, 2000)
                ->withHeaders($headers)
                ->post($url, $payload);
            
            $body = $response->json();
            if ($response->failed()) {
                return $body ?? ['result' => 'FAIL', 'resultcode' => (string) $response->status(), 'message' => $response->body()];
            }
            return $body;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['result' => 'FAIL', 'resultcode' => 'CONNECTION_ERROR', 'message' => 'Connection timeout: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['result' => 'FAIL', 'resultcode' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * Create order (full) – for hosted checkout that supports card and bank payments.
     * Requires payment_methods and billing.* fields; does NOT accept the "expiry" field.
     * POST /v1/checkout/create-order
     */
    public function createOrder(array $payload): array
    {
        $signedFields = array_keys($payload);
        $url = $this->baseUrl . '/checkout/create-order';
        $headers = $this->buildHeaders($signedFields, $payload);

        try {
            $response = Http::connectTimeout(30)
                ->timeout(90)
                ->retry(3, 2000)
                ->withHeaders($headers)
                ->post($url, $payload);

            $body = $response->json();
            if ($response->failed()) {
                return $body ?? ['result' => 'FAIL', 'resultcode' => (string) $response->status(), 'message' => $response->body()];
            }
            return $body;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['result' => 'FAIL', 'resultcode' => 'CONNECTION_ERROR', 'message' => 'Connection timeout: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['result' => 'FAIL', 'resultcode' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * Process order – wallet pull (push USSD to customer).
     * POST /v1/checkout/wallet-payment
     */
    public function walletPayment(string $transid, string $orderId, string $msisdn): array
    {
        $payload = [
            'transid' => $transid,
            'order_id' => $orderId,
            'msisdn' => $msisdn,
        ];
        $signedFields = ['transid', 'order_id', 'msisdn'];
        $url = $this->baseUrl . '/checkout/wallet-payment';
        $headers = $this->buildHeaders($signedFields, $payload);

        try {
            $response = Http::connectTimeout(30)
                ->timeout(90)
                ->retry(3, 2000)
                ->withHeaders($headers)
                ->post($url, $payload);
            
            $body = $response->json();
            if ($response->failed()) {
                return $body ?? ['result' => 'FAIL', 'resultcode' => (string) $response->status(), 'message' => $response->body()];
            }
            return $body;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['result' => 'FAIL', 'resultcode' => 'CONNECTION_ERROR', 'message' => 'Connection timeout: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['result' => 'FAIL', 'resultcode' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get order status.
     * GET /v1/checkout/order-status?order_id=...
     */
    public function orderStatus(string $orderId): array
    {
        $payload = ['order_id' => $orderId];
        $signedFields = ['order_id'];
        $url = $this->baseUrl . '/checkout/order-status?' . http_build_query($payload);
        $headers = $this->buildHeaders($signedFields, $payload);

        try {
            $response = Http::connectTimeout(30)
                ->timeout(90)
                ->retry(3, 2000)
                ->withHeaders($headers)
                ->get($url);
            
            $body = $response->json();
            if ($response->failed()) {
                return $body ?? ['result' => 'FAIL', 'resultcode' => (string) $response->status(), 'message' => $response->body()];
            }
            return $body;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['result' => 'FAIL', 'resultcode' => 'CONNECTION_ERROR', 'message' => 'Connection timeout: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['result' => 'FAIL', 'resultcode' => 'ERROR', 'message' => $e->getMessage()];
        }
    }
}
