<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\SelcomBusinessApiService;
use App\Services\SelcomBusinessCredentialResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Returns the Selcom Business account balance for the superadmin dashboard widget.
 * Called via AJAX so Selcom latency/failure never blocks the dashboard render.
 */
class SelcomBusinessBalanceController extends Controller
{
    public function __invoke(SelcomBusinessCredentialResolver $resolver): JsonResponse
    {
        $creds = $resolver->resolve();

        if (trim($creds['api_key']) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Enter the Business API key below to enable the balance check.',
            ], 200);
        }

        if (trim($creds['account_number']) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Enter the Business account number below to see the balance.',
            ], 200);
        }

        if (trim($creds['private_key_path']) === '' || ! is_file($creds['private_key_path']) || ! is_readable($creds['private_key_path'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Business private key (.pem) is missing or unreadable on the server.',
            ], 200);
        }

        try {
            $api = new SelcomBusinessApiService($creds['api_key'], $creds['private_key_path'], $creds['live']);
            $response = $api->balance($creds['account_number']);
        } catch (\Throwable $e) {
            Log::error('Selcom Business balance error', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Could not reach Selcom Business.'], 200);
        }

        $resultcode = isset($response['resultcode']) ? (string) $response['resultcode'] : null;
        $success = $response['success'] ?? null;

        if ($resultcode !== '000' && $success !== true) {
            $msg = trim((string) ($response['message'] ?? $response['result'] ?? 'Selcom did not return a balance.'));

            return response()->json(['ok' => false, 'message' => $msg !== '' ? $msg : 'Selcom did not return a balance.'], 200);
        }

        $data = $response['data'] ?? [];

        return response()->json([
            'ok' => true,
            'available_balance' => isset($data['available_balance']) ? (float) $data['available_balance'] : null,
            'currency' => (string) ($data['currency'] ?? 'TZS'),
            'account_number' => (string) ($data['account_number'] ?? $creds['account_number']),
            'active' => (bool) ($data['active'] ?? true),
            'live' => (bool) $creds['live'],
        ], 200);
    }
}
