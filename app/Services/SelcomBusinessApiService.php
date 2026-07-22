<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Selcom Business (disbursement) API client.
 *
 * Sends money OUT (agent commission payout) and authenticates with RSA-SHA256
 * signatures generated from a downloaded private key (.pem). This is a different
 * product/auth scheme from {@see SelcomApiService} (Checkout / HMAC collection).
 *
 * @see https://developer.selcom.business/
 */
class SelcomBusinessApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $privateKeyPath;

    public function __construct(?string $apiKey = null, ?string $privateKeyPath = null, ?bool $live = null)
    {
        $this->apiKey = $apiKey ?? (string) config('selcom_business.api_key');
        $this->privateKeyPath = $privateKeyPath ?? (string) config('selcom_business.private_key_path');
        $isLive = $live ?? (bool) config('selcom_business.live');

        $this->baseUrl = rtrim((string) ($isLive
            ? config('selcom_business.base_url.live')
            : config('selcom_business.base_url.sandbox')), '/');
    }

    /**
     * Send money to a recipient wallet/bank.
     * POST /v1/transaction/process
     *
     * @param array{transId:string,recipientFiCode:string,recipientAccount:string,recipientName:string,amount:int|float,purpose:string,remarks?:string} $payload
     */
    public function processTransaction(array $payload): array
    {
        // Sign the exact fields we send, in a stable order.
        $signedFields = ['transId', 'recipientFiCode', 'recipientAccount', 'recipientName', 'amount', 'purpose'];
        if (array_key_exists('remarks', $payload)) {
            $signedFields[] = 'remarks';
        }

        return $this->send('POST', '/v1/transaction/process', $payload, $signedFields);
    }

    /**
     * Verify a recipient account (and, with amount, get charges) before sending.
     * GET /v1/account/lookup
     */
    public function accountLookup(string $bank, string $account, string $transId, int|float|null $amount = null): array
    {
        $query = [
            'bank' => $bank,
            'account' => $account,
            'transId' => $transId,
        ];
        if ($amount !== null) {
            $query['amount'] = $amount;
        }

        return $this->send('GET', '/v1/account/lookup', $query, array_keys($query));
    }

    /**
     * Query the status of a previously submitted transaction.
     * GET /v1/transaction/query
     */
    public function queryTransaction(string $transId): array
    {
        $query = ['transId' => $transId];

        return $this->send('GET', '/v1/transaction/query', $query, ['transId']);
    }

    /**
     * Fetch the available balance of a Selcom Business account/wallet.
     * POST /v1/balance
     *
     * Response: data.available_balance, data.currency, data.account_number, data.active.
     */
    public function balance(string $accountNumber): array
    {
        $payload = ['account_number' => $accountNumber];

        return $this->send('POST', '/v1/balance', $payload, ['account_number']);
    }

    /**
     * Map a normalized Tanzanian MSISDN (255XXXXXXXXX) to its Selcom recipient FI
     * (cash-in) code, or null if the network is not in the configured map.
     */
    public static function fiCodeForMsisdn(string $msisdn): ?string
    {
        $map = (array) config('selcom_business.wallet_fi_codes', []);

        return $map[substr($msisdn, 0, 5)] ?? null;
    }

    /**
     * Build RSA-SHA256 signed headers.
     *
     * Signing string: "timestamp={ts}&field1=value1&field2=value2..." (fields in
     * signed-fields order; timestamp leads but is NOT part of signed-fields).
     * digest = Base64(RSA_SHA256(signing_string, private_key)).
     */
    protected function buildHeaders(array $signedFields, array $data): array
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s.v\Z');

        $parts = ["timestamp={$timestamp}"];
        foreach ($signedFields as $key) {
            $value = $data[$key] ?? '';
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $parts[] = "{$key}=" . (string) $value;
        }
        $signingString = implode('&', $parts);

        $signature = '';
        $ok = openssl_sign($signingString, $signature, $this->loadPrivateKey(), OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Failed to RSA-sign the Selcom Business request.');
        }

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'api-key' => $this->apiKey,
            'timestamp' => $timestamp,
            'digest' => base64_encode($signature),
            'signed-fields' => implode(',', $signedFields),
        ];
    }

    /**
     * @return \OpenSSLAsymmetricKey|string
     */
    protected function loadPrivateKey()
    {
        if ($this->privateKeyPath === '' || ! is_file($this->privateKeyPath) || ! is_readable($this->privateKeyPath)) {
            throw new RuntimeException('Selcom Business private key not found or unreadable at: ' . $this->privateKeyPath);
        }

        $pem = (string) file_get_contents($this->privateKeyPath);
        $key = openssl_pkey_get_private($pem);
        if ($key === false) {
            throw new RuntimeException('Selcom Business private key is invalid (openssl could not parse it).');
        }

        return $key;
    }

    protected function send(string $method, string $path, array $data, array $signedFields): array
    {
        $url = $this->baseUrl . $path;

        try {
            $headers = $this->buildHeaders($signedFields, $data);

            $request = Http::connectTimeout(30)
                ->timeout(90)
                ->retry(2, 2000, throw: false)
                ->withHeaders($headers);

            $response = $method === 'GET'
                ? $request->get($url, $data)
                : $request->post($url, $data);

            $body = $response->json();

            if ($response->failed()) {
                Log::warning('Selcom Business request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $body ?? $response->body(),
                ]);

                return $body ?? [
                    'success' => false,
                    'error_code' => $response->status(),
                    'message' => 'HTTP ' . $response->status(),
                    'result' => 'FAIL',
                    'resultcode' => (string) $response->status(),
                ];
            }

            return is_array($body) ? $body : [
                'success' => false,
                'message' => 'Unexpected non-JSON response.',
                'result' => 'FAIL',
                'resultcode' => 'BAD_RESPONSE',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['success' => false, 'message' => 'Connection error: ' . $e->getMessage(), 'result' => 'FAIL', 'resultcode' => 'CONNECTION_ERROR'];
        } catch (\Throwable $e) {
            Log::error('Selcom Business request error', ['path' => $path, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage(), 'result' => 'FAIL', 'resultcode' => 'ERROR'];
        }
    }
}
