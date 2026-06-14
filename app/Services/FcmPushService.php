<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    private ?string $cachedAccessToken = null;

    private ?int $tokenExpiresAt = null;

    public function isConfigured(): bool
    {
        if (! config('firebase.enabled')) {
            return false;
        }

        $credentials = config('firebase.credentials');

        return is_string($credentials)
            && $credentials !== ''
            && is_file($credentials)
            && $this->projectId() !== null;
    }

    private function projectId(): ?string
    {
        $configured = config('firebase.project_id');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $credentials = config('firebase.credentials');
        if (! is_string($credentials) || ! is_file($credentials)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($credentials), true);

        return is_array($json) ? ($json['project_id'] ?? null) : null;
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $tokens = array_values(array_unique(array_filter(array_map('strval', $tokens))));
        if ($tokens === []) {
            return;
        }

        try {
            $accessToken = $this->accessToken();
            $projectId = (string) $this->projectId();

            foreach ($tokens as $token) {
                $payload = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $this->stringifyData(array_merge($data, [
                            'title' => $title,
                            'body' => $body,
                        ])),
                        'android' => [
                            'priority' => 'HIGH',
                            'notification' => [
                                'channel_id' => 'optic_alerts',
                                'sound' => 'default',
                            ],
                        ],
                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10',
                            ],
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                ];

                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

                if (! $response->successful()) {
                    Log::warning('FCM send failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'token_prefix' => substr($token, 0, 12),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('FCM push error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stringifyData(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_scalar($value)) {
                $out[(string) $key] = (string) $value;
            } else {
                $out[(string) $key] = json_encode($value);
            }
        }

        return $out;
    }

    private function accessToken(): string
    {
        if ($this->cachedAccessToken && $this->tokenExpiresAt && time() < ($this->tokenExpiresAt - 60)) {
            return $this->cachedAccessToken;
        }

        $credentialsPath = config('firebase.credentials');
        $json = json_decode((string) file_get_contents($credentialsPath), true);
        if (! is_array($json)) {
            throw new \RuntimeException('Invalid Firebase credentials JSON.');
        }

        $clientEmail = $json['client_email'] ?? null;
        $privateKey = $json['private_key'] ?? null;
        if (! is_string($clientEmail) || ! is_string($privateKey)) {
            throw new \RuntimeException('Firebase credentials missing client_email or private_key.');
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $unsigned = $header.'.'.$claim;

        $signature = '';
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new \RuntimeException('Unable to parse Firebase private key.');
        }
        openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
        $jwt = $unsigned.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Unable to obtain Firebase access token.');
        }

        $this->cachedAccessToken = (string) $response->json('access_token');
        $this->tokenExpiresAt = $now + (int) ($response->json('expires_in') ?? 3600);

        return $this->cachedAccessToken;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
