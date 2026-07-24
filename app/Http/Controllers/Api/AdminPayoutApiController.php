<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\Selcompay;
use App\Services\AgentCommissionExpenseService;
use App\Services\DefaultAgentCommissionService;
use App\Services\SelcomAgentCommissionCheckoutService;
use App\Services\SelcomApiService;
use App\Services\SelcomCredentialResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminPayoutApiController extends Controller
{
    private const EPS = 0.0001;

    public function index(): JsonResponse
    {
        $completedKeys = [];
        if (Schema::hasTable('selcompays') && Schema::hasColumn('selcompays', 'purpose')) {
            $completedKeys = Selcompay::query()
                ->where('purpose', Selcompay::PURPOSE_AGENT_COMMISSION_DISBURSE)
                ->where('payment_status', 'completed')
                ->whereNotNull('payout_source_type')
                ->whereNotNull('payout_source_id')
                ->get(['payout_source_type', 'payout_source_id'])
                ->map(fn (Selcompay $s) => $s->payout_source_type.':'.$s->payout_source_id)
                ->unique()
                ->values()
                ->all();
            $completedKeys = array_fill_keys($completedKeys, true);
        }

        $raw = collect();

        foreach (AgentCredit::query()->with(['agent:id,name,phone'])->where('commission_paid', '>', self::EPS)->orderByDesc('id')->limit(200)->get() as $c) {
            $agentId = (int) ($c->agent_id ?: 0);
            if ($agentId <= 0) {
                continue;
            }
            $date = $c->date ? Carbon::parse($c->date)->toDateString() : Carbon::parse($c->created_at)->toDateString();
            $key = $date.'|agent:'.$agentId;
            $sourceKey = 'agent_credit:'.$c->id;
            $raw->push([
                'group_key' => $key,
                'date' => $date,
                'agent_id' => $agentId,
                'agent_name' => $c->agent?->name ?? '—',
                'mobile' => $c->agent?->phone ?? '—',
                'quantity' => 1,
                'commission_amount' => (float) $c->commission_paid,
                'disburse_completed' => isset($completedKeys[$sourceKey]),
                'payout_booked' => $c->commission_expense_id !== null,
                'source' => 'credit',
                'source_id' => $c->id,
            ]);
        }

        foreach (AgentSale::query()->with(['agent:id,name,phone'])->where('commission_paid', '>', self::EPS)->orderByDesc('id')->limit(200)->get() as $s) {
            $agentId = (int) ($s->agent_id ?: 0);
            if ($agentId <= 0) {
                continue;
            }
            $date = $s->date ? Carbon::parse($s->date)->toDateString() : Carbon::parse($s->created_at)->toDateString();
            $key = $date.'|agent:'.$agentId;
            $sourceKey = 'agent_sale:'.$s->id;
            $raw->push([
                'group_key' => $key,
                'date' => $date,
                'agent_id' => $agentId,
                'agent_name' => $s->agent?->name ?? ($s->seller_name ?: '—'),
                'mobile' => $s->agent?->phone ?? '—',
                'quantity' => max(1, (int) ($s->quantity_sold ?? 1)),
                'commission_amount' => (float) $s->commission_paid,
                'disburse_completed' => isset($completedKeys[$sourceKey]),
                'payout_booked' => $s->commission_expense_id !== null,
                'source' => 'sale',
                'source_id' => $s->id,
            ]);
        }

        $groups = $raw->groupBy('group_key')->map(function ($items) {
            $first = $items->first();
            $allDisbursed = $items->every(fn ($i) => ! empty($i['disburse_completed']));
            $anyDisbursed = $items->contains(fn ($i) => ! empty($i['disburse_completed']));

            return [
                'date' => $first['date'],
                'agent_id' => $first['agent_id'],
                'agent_name' => $first['agent_name'],
                'mobile' => $first['mobile'],
                'devices' => (int) $items->sum('quantity'),
                'commission_amount' => round($items->sum('commission_amount'), 2),
                'disburse_completed' => $allDisbursed,
                'edit_locked' => $anyDisbursed,
                'payout_booked' => $items->every(fn ($i) => ! empty($i['payout_booked'])),
                'lines' => $items->map(fn ($i) => [
                    'source' => $i['source'],
                    'source_id' => $i['source_id'],
                    'commission_amount' => $i['commission_amount'],
                ])->values(),
            ];
        })->sortByDesc('date')->values();

        $dateTabs = $groups->groupBy('date')->map(function ($dayGroups, $date) {
            return [
                'date' => $date,
                'agents' => $dayGroups->values(),
                'total_devices' => (int) $dayGroups->sum('devices'),
                'total_commission' => round($dayGroups->sum('commission_amount'), 2),
            ];
        })->sortKeysDesc()->values();

        return response()->json([
            'default_commission_amount' => app(DefaultAgentCommissionService::class)->getAmount(),
            'data' => $groups,
            'date_tabs' => $dateTabs,
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
                $createdAt = Carbon::parse($selcompay->created_at);
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
            Log::error('Selcom commission status failed: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['status' => 'error', 'message' => 'Status check failed.'], 500);
        }
    }
}
