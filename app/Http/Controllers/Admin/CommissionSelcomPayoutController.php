<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Selcompay;
use App\Services\SelcomAgentCommissionCheckoutService;
use App\Services\SelcomApiService;
use App\Services\SelcomCredentialResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CommissionSelcomPayoutController extends Controller
{
    public function bulkStart(): RedirectResponse
    {
        $summary = app(SelcomAgentCommissionCheckoutService::class)->bulkInitiateEligibleLines();

        return redirect()
            ->route('admin.payout.index')
            ->with('bulk_selcom_summary', $summary);
    }

    public function wait(Selcompay $selcompay): View
    {
        $this->ensureCommissionCheckout($selcompay);

        return view('admin.payout.selcom-wait', compact('selcompay'));
    }

    public function status(Selcompay $selcompay): JsonResponse
    {
        $this->ensureCommissionCheckout($selcompay);

        try {
            if (! $selcompay->order_id) {
                $createdAt = \Carbon\Carbon::parse($selcompay->created_at);
                if ($createdAt->diffInMinutes(now()) > 10) {
                    $selcompay->update(['payment_status' => 'timeout']);

                    return response()->json(['status' => 'timeout', 'message' => 'Checkout timed out.']);
                }

                return response()->json(['status' => 'pending', 'message' => 'Waiting for Selcom…']);
            }

            $creds = app(SelcomCredentialResolver::class)->resolve();
            $selcom = new SelcomApiService(
                $creds['vendor'],
                $creds['api_key'],
                $creds['api_secret'],
                $creds['live']
            );

            $statusArr = $selcom->orderStatus($selcompay->order_id);

            Log::info('Selcom commission checkout status', [
                'selcompay_id' => $selcompay->id,
                'order_id' => $selcompay->order_id,
                'response' => $statusArr,
            ]);

            if (! isset($statusArr['resultcode'])) {
                return response()->json(['status' => 'error', 'message' => 'Unable to read Selcom status.']);
            }

            if ($statusArr['resultcode'] !== '000') {
                $errorMessage = $statusArr['message'] ?? $statusArr['result'] ?? 'Unknown error';

                return response()->json(['status' => 'error', 'message' => $errorMessage]);
            }

            $paymentStatus = $statusArr['data'][0]['payment_status'] ?? null;

            if ($paymentStatus === 'COMPLETED') {
                $selcompay->update(['payment_status' => 'completed']);

                return response()->json(['status' => 'completed', 'message' => 'Selcom reports completed.']);
            }

            if (in_array($paymentStatus, ['FAILED', 'CANCELLED', 'EXPIRED', 'REJECTED', 'USERCANCELLED'], true)) {
                $selcompay->update(['payment_status' => 'failed']);

                return response()->json(['status' => 'failed', 'message' => 'Payment ' . strtolower((string) $paymentStatus) . '.']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Still processing…']);
        } catch (\Throwable $e) {
            Log::error('Selcom commission status error', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    protected function ensureCommissionCheckout(Selcompay $selcompay): void
    {
        if ($selcompay->purpose !== Selcompay::PURPOSE_AGENT_COMMISSION_CHECKOUT) {
            abort(404);
        }
    }
}
