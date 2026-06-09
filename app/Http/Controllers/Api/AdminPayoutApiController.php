<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\Selcompay;
use App\Services\AgentCommissionExpenseService;
use App\Services\SelcomAgentCommissionCheckoutService;
use App\Services\SelcomApiService;
use App\Services\SelcomCredentialResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminPayoutApiController extends Controller
{
    public function index(): JsonResponse
    {
        $eps = 0.0001;
        $rows = collect();

        foreach (AgentCredit::query()->with(['agent:id,name,phone'])->where('commission_paid', '>', $eps)->orderByDesc('id')->limit(100)->get() as $c) {
            $rows->push([
                'source' => 'credit',
                'source_id' => $c->id,
                'agent_name' => $c->agent?->name ?? '—',
                'mobile' => $c->agent?->phone ?? '—',
                'commission_amount' => (float) $c->commission_paid,
                'payout_booked' => $c->commission_expense_id !== null,
            ]);
        }

        foreach (AgentSale::query()->with(['agent:id,name,phone'])->where('commission_paid', '>', $eps)->orderByDesc('id')->limit(100)->get() as $s) {
            $rows->push([
                'source' => 'sale',
                'source_id' => $s->id,
                'agent_name' => $s->agent?->name ?? '—',
                'mobile' => $s->agent?->phone ?? '—',
                'commission_amount' => (float) $s->commission_paid,
                'payout_booked' => $s->commission_expense_id !== null,
            ]);
        }

        return response()->json([
            'data' => $rows->sortByDesc('commission_amount')->values(),
        ]);
    }

    public function bulkSelcom(): JsonResponse
    {
        $summary = app(SelcomAgentCommissionCheckoutService::class)->bulkInitiateEligibleLines();

        return response()->json([
            'message' => 'Bulk Selcom payout initiated.',
            'data' => $summary,
        ]);
    }

    public function selcomStatus(Selcompay $selcompay): JsonResponse
    {
        if ($selcompay->purpose !== Selcompay::PURPOSE_AGENT_COMMISSION_CHECKOUT) {
            abort(404);
        }

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
                app(AgentCommissionExpenseService::class)->bookFromSelcompay($selcompay->fresh());

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
}
