<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\AgentCreditPayment;
use App\Models\PaymentOption;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAgentCreditsAdminApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $base = AgentCredit::query();

        if ($request->filled('date_from')) {
            $base->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $base->whereDate('date', '<=', $request->date_to);
        }

        $statsQuery = clone $base;
        $sumTotal = (float) (clone $statsQuery)->sum('total_amount');
        $sumPaid = (float) (clone $statsQuery)->sum('paid_amount');

        $credits = (clone $base)
            ->with(['agent:id,name,phone', 'product.category', 'paymentOption:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (AgentCredit $c) => $this->serializeCredit($c));

        return response()->json([
            'data' => $credits,
            'stats' => [
                'count' => (clone $statsQuery)->count(),
                'total_credit' => $sumTotal,
                'total_paid' => $sumPaid,
                'total_pending' => max(0, $sumTotal - $sumPaid),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $credit = AgentCredit::query()
            ->with(['agent', 'product.category', 'productListItem', 'paymentOption'])
            ->findOrFail($id);

        $payments = AgentCreditPayment::query()
            ->where('agent_credit_id', $credit->id)
            ->with('paymentOption:id,name')
            ->orderByDesc('paid_date')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'paid_date' => optional($p->paid_date)->toDateString(),
                'payment_option_name' => $p->paymentOption?->name,
            ]);

        return response()->json([
            'data' => array_merge($this->serializeCredit($credit), [
                'payments' => $payments,
            ]),
        ]);
    }

    public function pay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_credit_id' => 'required|integer|exists:agent_credits,id',
            'paid_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_option_id' => 'nullable|integer|exists:payment_options,id',
        ]);

        $credit = AgentCredit::query()->findOrFail((int) $validated['agent_credit_id']);
        $totalAmount = (float) $credit->total_amount;
        $oldPaid = (float) ($credit->paid_amount ?? 0);
        $remaining = max(0, $totalAmount - $oldPaid);
        $amount = (float) $validated['amount'];
        $eps = 0.0001;

        if ($remaining <= $eps) {
            return response()->json(['message' => 'This credit is already fully paid.'], 422);
        }
        if ($amount > $remaining + $eps) {
            return response()->json([
                'message' => 'Amount cannot exceed pending balance ('.number_format($remaining, 2).').',
            ], 422);
        }

        if (! Schema::hasTable('payment_options')) {
            return response()->json(['message' => 'Payment channels are not configured.'], 422);
        }

        $paymentOptionId = $validated['payment_option_id'] ?? null;
        if (! $paymentOptionId) {
            $defaultWatu = Setting::query()->where('key', 'default_watu_channel_id')->value('value');
            $paymentOptionId = $defaultWatu ? (int) $defaultWatu : null;
        }
        if (! $paymentOptionId) {
            return response()->json(['message' => 'Select a payment channel.'], 422);
        }

        DB::transaction(function () use ($credit, $amount, $validated, $paymentOptionId, $oldPaid, $totalAmount) {
            AgentCreditPayment::create([
                'agent_credit_id' => $credit->id,
                'amount' => $amount,
                'paid_date' => $validated['paid_date'],
                'payment_option_id' => $paymentOptionId,
            ]);

            $newPaid = $oldPaid + $amount;
            $credit->update([
                'paid_amount' => $newPaid,
                'payment_status' => $newPaid + 0.0001 >= $totalAmount ? 'paid' : 'partial',
            ]);
        });

        return response()->json(['message' => 'Payment recorded.']);
    }

    private function serializeCredit(AgentCredit $c): array
    {
        $total = (float) $c->total_amount;
        $paid = (float) ($c->paid_amount ?? 0);

        return [
            'id' => $c->id,
            'date' => optional($c->date)->toDateString(),
            'agent_id' => $c->agent_id,
            'agent_name' => $c->agent?->name,
            'agent_phone' => $c->agent?->phone,
            'product_name' => $c->product?->name,
            'category_name' => $c->product?->category?->name,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'pending_amount' => max(0, $total - $paid),
            'payment_status' => $c->payment_status,
            'payment_option_name' => $c->paymentOption?->name,
            'commission_paid' => (float) ($c->commission_paid ?? 0),
        ];
    }
}
