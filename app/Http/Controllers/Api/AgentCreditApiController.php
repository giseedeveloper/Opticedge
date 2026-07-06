<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\AgentCreditPayment;
use App\Models\PaymentOption;
use App\Support\PdfDownload;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgentCreditApiController extends Controller
{
    private function ensureTenantContext(): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        if ($user->isSuperadmin()) {
            TenantContext::bypass();

            return;
        }

        if ($user->tenant_id !== null) {
            TenantContext::set((int) $user->tenant_id);
        }
    }

    /**
     * Credits sold by the authenticated agent (newest first).
     */
    public function index(Request $request)
    {
        $this->ensureTenantContext();

        $agentId = Auth::id();

        $query = AgentCredit::query()
            ->where('agent_id', $agentId)
            ->with(['product.category', 'productListItem'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        $credits = $query->get()->map(function (AgentCredit $credit) {
            $total = (float) $credit->total_amount;
            $paid = (float) ($credit->paid_amount ?? 0);
            $remaining = max(0, $total - $paid);
            $product = $credit->product;
            $label = $product
                ? (($product->category?->name ?? '—').' – '.$product->name)
                : '—';

            return [
                'id' => $credit->id,
                'customer_name' => $credit->customer_name,
                'customer_phone' => Schema::hasColumn('agent_credits', 'customer_phone')
                    ? $credit->customer_phone
                    : null,
                'kin_name' => Schema::hasColumn('agent_credits', 'kin_name')
                    ? $credit->kin_name
                    : null,
                'kin_phone' => Schema::hasColumn('agent_credits', 'kin_phone')
                    ? $credit->kin_phone
                    : null,
                'description' => $credit->installment_notes,
                'date' => $credit->date instanceof \Carbon\Carbon
                    ? $credit->date->format('Y-m-d')
                    : (string) $credit->date,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining' => $remaining,
                'payment_status' => $credit->payment_status,
                'product_label' => $label,
                'imei_number' => $credit->productListItem?->imei_number,
                'installment_count' => $credit->installment_count,
                'installment_amount' => $credit->installment_amount !== null
                    ? (float) $credit->installment_amount
                    : null,
                'first_due_date' => $credit->first_due_date instanceof \Carbon\Carbon
                    ? $credit->first_due_date->format('Y-m-d')
                    : ($credit->first_due_date ? (string) $credit->first_due_date : null),
                'invoice_available' => true,
                'invoice_endpoint' => '/agent/credits/' . $credit->id . '/invoice',
            ];
        });

        return response()->json([
            'data' => $credits,
        ]);
    }

    /**
     * Record an installment / repayment on a credit owned by this agent.
     */
    public function payInstallment(Request $request, int $id)
    {
        $this->ensureTenantContext();

        $rules = [
            'amount' => 'required|numeric|min:0.01',
            'paid_date' => 'nullable|date',
        ];
        if (Schema::hasTable('payment_options')) {
            $rules['payment_option_id'] = 'required|exists:payment_options,id';
        } else {
            $rules['payment_option_id'] = 'nullable';
        }

        $validated = $request->validate($rules);

        $credit = AgentCredit::where('agent_id', Auth::id())->findOrFail($id);

        $totalAmount = (float) $credit->total_amount;
        $oldPaid = (float) ($credit->paid_amount ?? 0);
        $remaining = max(0, $totalAmount - $oldPaid);
        $eps = 0.0001;
        $increment = (float) $validated['amount'];

        if ($increment > $remaining + $eps) {
            return response()->json([
                'message' => 'Amount cannot exceed the remaining balance ('.number_format($remaining, 2).').',
            ], 422);
        }

        $paymentOptionId = isset($validated['payment_option_id'])
            ? (int) $validated['payment_option_id']
            : null;

        if ($paymentOptionId === null) {
            return response()->json([
                'message' => 'Select a payment channel.',
            ], 422);
        }

        $opt = PaymentOption::visible()->whereKey($paymentOptionId)->first();
        if (! $opt) {
            return response()->json([
                'message' => 'Invalid or hidden payment channel.',
            ], 422);
        }

        $newPaid = min($totalAmount, $oldPaid + $increment);
        $paymentStatus = $newPaid >= $totalAmount - $eps ? 'paid' : ($newPaid > $eps ? 'partial' : 'pending');
        $paidDate = $validated['paid_date'] ?? now()->toDateString();

        DB::transaction(function () use ($credit, $opt, $increment, $newPaid, $paymentStatus, $paidDate, $paymentOptionId, $eps) {
            // Same as admin agent sale channel: repayment is credited to the selected channel.
            if ($increment > $eps) {
                $opt->increment('balance', $increment);
            }

            $update = [
                'paid_amount' => $newPaid,
                'payment_status' => $paymentStatus,
                'paid_date' => $paidDate,
            ];
            if (Schema::hasColumn('agent_credits', 'payment_option_id')) {
                $update['payment_option_id'] = $paymentOptionId;
            }
            $credit->update($update);

            AgentCreditPayment::create([
                'agent_credit_id' => $credit->id,
                'payment_option_id' => $paymentOptionId,
                'amount' => $increment,
                'paid_date' => $paidDate,
            ]);
        });

        $credit->refresh();

        return response()->json([
            'message' => 'Payment recorded.',
            'data' => [
                'agent_credit_id' => $credit->id,
                'paid_amount' => (float) $credit->paid_amount,
                'remaining' => max(0, (float) $credit->total_amount - (float) $credit->paid_amount),
                'payment_status' => $credit->payment_status,
            ],
        ], 200);
    }

    public function show(int $id)
    {
        $this->ensureTenantContext();

        $credit = AgentCredit::query()
            ->where('agent_id', Auth::id())
            ->with(['product.category', 'productListItem', 'paymentOption', 'payments.paymentOption'])
            ->findOrFail($id);

        $total = (float) $credit->total_amount;
        $paid = (float) ($credit->paid_amount ?? 0);

        return response()->json([
            'data' => [
                'id' => $credit->id,
                'customer_name' => $credit->customer_name,
                'customer_phone' => $credit->customer_phone,
                'kin_name' => $credit->kin_name,
                'kin_phone' => $credit->kin_phone,
                'description' => $credit->installment_notes,
                'date' => $credit->date ? $credit->date->format('Y-m-d') : null,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining' => max(0, $total - $paid),
                'payment_status' => $credit->payment_status,
                'product_label' => ($credit->product?->category?->name ?? '—').' – '.($credit->product?->name ?? '—'),
                'imei_number' => $credit->productListItem?->imei_number,
                'installment_count' => $credit->installment_count,
                'installment_amount' => $credit->installment_amount !== null ? (float) $credit->installment_amount : null,
                'first_due_date' => $credit->first_due_date ? $credit->first_due_date->format('Y-m-d') : null,
                'payment_option' => $credit->paymentOption ? [
                    'id' => $credit->paymentOption->id,
                    'name' => $credit->paymentOption->name,
                ] : null,
                'payments' => $credit->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => (float) $payment->amount,
                        'paid_date' => $payment->paid_date ? $payment->paid_date->format('Y-m-d') : null,
                        'notes' => $payment->notes,
                        'payment_option' => $payment->paymentOption ? [
                            'id' => $payment->paymentOption->id,
                            'name' => $payment->paymentOption->name,
                        ] : null,
                    ];
                })->values()->all(),
            ],
        ]);
    }

    public function downloadInvoice(int $id)
    {
        $this->ensureTenantContext();

        $credit = AgentCredit::query()
            ->where('agent_id', Auth::id())
            ->with(['product.category', 'productListItem'])
            ->findOrFail($id);

        $invoiceNo = 'AC-' . str_pad((string) $credit->id, 6, '0', STR_PAD_LEFT);
        $invoiceDate = $credit->paid_date ?? $credit->date ?? now();
        $filename = 'agent-credit-invoice-' . strtolower($invoiceNo) . '-' . $invoiceDate->format('Ymd') . '.pdf';
        $title = 'RECEIPT';

        return PdfDownload::fromView('admin.stock.receipt-invoice', [
            'credit' => $credit,
            'invoiceNo' => $invoiceNo,
            'invoiceDate' => $invoiceDate,
            'title' => $title,
        ], $filename);
    }
}
