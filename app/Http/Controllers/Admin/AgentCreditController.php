<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\AgentCreditPayment;
use App\Models\PaymentOption;
use App\Models\Setting;
use App\Services\AgentCommissionExpenseService;
use App\Support\PdfDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AgentCreditController extends Controller
{
    public function index(Request $request)
    {
        $base = $this->filteredAgentCreditsQuery($request);

        $statsQuery = clone $base;
        $aggregateSelect = 'COUNT(*) as aggregate_count, COALESCE(SUM(total_amount), 0) as aggregate_total, COALESCE(SUM(paid_amount), 0) as aggregate_paid';
        if (Schema::hasColumn('agent_credits', 'profit')) {
            $aggregateSelect .= ', COALESCE(SUM(profit), 0) as aggregate_profit';
        }
        $aggregates = (clone $statsQuery)->selectRaw($aggregateSelect)->first();
        $sumTotal = (float) ($aggregates->aggregate_total ?? 0);
        $sumPaid = (float) ($aggregates->aggregate_paid ?? 0);
        $agentCreditsDashboard = [
            'count' => (int) ($aggregates->aggregate_count ?? 0),
            'total_credit' => $sumTotal,
            'total_paid' => $sumPaid,
            'total_pending' => max(0, $sumTotal - $sumPaid),
            'total_profit' => Schema::hasColumn('agent_credits', 'profit')
                ? (float) ($aggregates->aggregate_profit ?? 0)
                : 0.0,
        ];

        $credits = $base->with(['agent.teamLeader', 'teamLeader', 'product.category', 'productListItem', 'paymentOption'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $paymentOptions = Schema::hasTable('payment_options')
            ? PaymentOption::visible()->orderBy('name')->get()
            : collect();

        $defaultWatuChannelId = Setting::query()->where('key', 'default_watu_channel_id')->value('value');
        $defaultWatuChannel = $defaultWatuChannelId
            ? PaymentOption::visible()->find((int) $defaultWatuChannelId)
            : null;

        $paymentHistory = AgentCreditPayment::query()
            ->with(['agentCredit.agent', 'agentCredit.product.category', 'paymentOption'])
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('paid_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('paid_date', '<=', $request->date_to))
            ->orderByDesc('paid_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('admin.stock.agent-credits', compact(
            'credits',
            'paymentOptions',
            'agentCreditsDashboard',
            'defaultWatuChannel',
            'paymentHistory'
        ));
    }

    public function pay(Request $request)
    {
        $validated = $request->validate([
            'paid_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = (float) $validated['amount'];
        $paidDate = $validated['paid_date'];
        $eps = 0.0001;

        $pendingCredits = $this->filteredAgentCreditsQuery($request)
            ->whereRaw('COALESCE(paid_amount, 0) < total_amount')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $totalPending = $pendingCredits->sum(function (AgentCredit $credit) {
            return max(0, (float) $credit->total_amount - (float) ($credit->paid_amount ?? 0));
        });

        if ($totalPending <= $eps) {
            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->with('info', 'There are no pending agent credits to pay.');
        }

        if ($amount > $totalPending + $eps) {
            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->withInput()
                ->withErrors(['amount' => 'Amount cannot exceed total pending balance (' . number_format($totalPending, 2) . ').']);
        }

        if (! Schema::hasTable('payment_options')) {
            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->withErrors(['error' => 'Payment channels are not configured.']);
        }

        $defaultWatuChannelRaw = Setting::query()->where('key', 'default_watu_channel_id')->value('value');
        $paymentOptionId = $defaultWatuChannelRaw !== null && $defaultWatuChannelRaw !== ''
            ? (int) $defaultWatuChannelRaw
            : null;
        if (! $paymentOptionId) {
            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->withErrors(['error' => 'Choose a default Watu channel in Store settings before recording payment.']);
        }

        $option = PaymentOption::query()->visible()->whereKey($paymentOptionId)->first();
        if (! $option) {
            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->withErrors(['error' => 'Default Watu channel is invalid or hidden. Update Store settings.']);
        }

        $creditsUpdated = 0;

        DB::transaction(function () use ($pendingCredits, $option, $paymentOptionId, $amount, $paidDate, $eps, &$creditsUpdated) {
            $option->increment('balance', $amount);

            $remainingToAllocate = $amount;

            foreach ($pendingCredits as $credit) {
                if ($remainingToAllocate <= $eps) {
                    break;
                }

                $creditTotal = (float) $credit->total_amount;
                $oldPaid = (float) ($credit->paid_amount ?? 0);
                $creditPending = max(0, $creditTotal - $oldPaid);

                if ($creditPending <= $eps) {
                    continue;
                }

                $applied = min($creditPending, $remainingToAllocate);
                $newPaid = min($creditTotal, $oldPaid + $applied);
                $status = $newPaid >= $creditTotal - $eps ? 'paid' : ($newPaid > $eps ? 'partial' : 'pending');
                $update = [
                    'paid_amount' => $newPaid,
                    'payment_status' => $status,
                    'paid_date' => $paidDate,
                ];
                if (Schema::hasColumn('agent_credits', 'payment_option_id')) {
                    $update['payment_option_id'] = $paymentOptionId;
                }
                $credit->update($update);

                AgentCreditPayment::create([
                    'agent_credit_id' => $credit->id,
                    'payment_option_id' => $paymentOptionId,
                    'amount' => $applied,
                    'paid_date' => $paidDate,
                ]);

                $remainingToAllocate -= $applied;
                $creditsUpdated++;
            }
        });

        return redirect()
            ->route('admin.stock.agent-credits', $request->query())
            ->with('success', 'Payment recorded across ' . $creditsUpdated . ' credit(s) and totals updated.');
    }

    public function destroyPayment(Request $request, int $paymentId)
    {
        $payment = AgentCreditPayment::with(['agentCredit', 'paymentOption'])->findOrFail($paymentId);
        $credit = $payment->agentCredit;

        if (! $credit) {
            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->withErrors(['error' => 'Credit not found for this payment.']);
        }

        $amount = (float) $payment->amount;
        $eps = 0.0001;

        DB::transaction(function () use ($payment, $credit, $amount, $eps) {
            if ($amount > $eps && $payment->paymentOption) {
                $payment->paymentOption->decrement('balance', $amount);
            }

            $oldPaid = (float) ($credit->paid_amount ?? 0);
            $newPaid = max(0, $oldPaid - $amount);
            $total = (float) $credit->total_amount;
            $status = $newPaid >= $total - $eps ? 'paid' : ($newPaid > $eps ? 'partial' : 'pending');

            $latestRemaining = AgentCreditPayment::query()
                ->where('agent_credit_id', $credit->id)
                ->where('id', '!=', $payment->id)
                ->orderByDesc('paid_date')
                ->orderByDesc('id')
                ->first();

            $credit->update([
                'paid_amount' => $newPaid,
                'payment_status' => $status,
                'paid_date' => $newPaid > $eps ? $latestRemaining?->paid_date : null,
            ]);

            $payment->delete();
        });

        return redirect()
            ->route('admin.stock.agent-credits', $request->query())
            ->with('success', 'Payment deleted. Credit balance and channel updated.');
    }

    private function filteredAgentCreditsQuery(Request $request)
    {
        $query = AgentCredit::query();

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        return $query;
    }

    public function exportCsv(Request $request)
    {
        $query = AgentCredit::with(['agent', 'product.category', 'productListItem', 'paymentOption']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        $credits = $query->orderByDesc('date')->orderByDesc('id')->get();
        $filename = 'agent-credits-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($credits) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date',
                'Agent',
                'Customer',
                'Product',
                'IMEI',
                'Buy',
                'Sell',
                'Profit',
                'Total Amount',
                'Paid Amount',
                'Pending Amount',
                'Payment Channel',
                'Status',
            ]);

            foreach ($credits as $credit) {
                $total = (float) ($credit->total_amount ?? 0);
                $paid = (float) ($credit->paid_amount ?? 0);

                fputcsv($handle, [
                    $credit->date ? (string) $credit->date : '',
                    $credit->agent?->name ?? '',
                    $credit->customer_name ?? '',
                    trim(($credit->product?->category?->name ? $credit->product->category->name . ' - ' : '') . ($credit->product?->name ?? '')),
                    $credit->productListItem?->imei_number ?? '',
                    number_format($credit->displayPurchasePrice(), 2, '.', ''),
                    number_format($credit->displaySellingPrice(), 2, '.', ''),
                    number_format($credit->displayProfit(), 2, '.', ''),
                    number_format($total, 2, '.', ''),
                    number_format($paid, 2, '.', ''),
                    number_format(max(0, $total - $paid), 2, '.', ''),
                    $credit->paymentOption?->name ?? '',
                    $credit->payment_status ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function edit(int $id)
    {
        $credit = AgentCredit::with(['agent', 'product.category', 'productListItem', 'payments.paymentOption'])
            ->findOrFail($id);
        $paymentOptions = Schema::hasTable('payment_options')
            ? PaymentOption::visible()->orderBy('name')->get()
            : collect();

        return view('admin.stock.edit-agent-credit', compact('credit', 'paymentOptions'));
    }

    public function downloadInvoice(int $id)
    {
        $credit = AgentCredit::with(['agent', 'product.category', 'productListItem'])->findOrFail($id);

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

    /**
     * Update only the payment channel from the agent credits list (balances adjusted like full update).
     */
    public function updatePaymentChannel(Request $request, int $id)
    {
        $credit = AgentCredit::findOrFail($id);

        $rules = [
            'payment_option_id' => Schema::hasTable('payment_options')
                ? 'nullable|exists:payment_options,id'
                : 'nullable',
        ];
        $validated = $request->validate($rules);

        $totalAmount = (float) $credit->total_amount;
        $oldPaidAmount = (float) ($credit->paid_amount ?? 0);
        $eps = 0.0001;

        $newPaidAmount = min($totalAmount, $oldPaidAmount);
        $paymentDifference = 0.0;

        $paymentStatus = $newPaidAmount >= $totalAmount - $eps ? 'paid' : ($newPaidAmount > $eps ? 'partial' : 'pending');

        $oldPaymentOption = $credit->payment_option_id;
        $newPaymentOptionId = $validated['payment_option_id'] ?? null;
        if ($newPaymentOptionId === '' || $newPaymentOptionId === false) {
            $newPaymentOptionId = null;
        } else {
            $newPaymentOptionId = (int) $newPaymentOptionId;
        }

        $oldOptId = $oldPaymentOption !== null ? (int) $oldPaymentOption : null;
        $newOptId = $newPaymentOptionId;

        if ($newOptId === null && $oldOptId !== null && $oldPaidAmount > $eps) {
            $oldOption = PaymentOption::find($oldOptId);
            if ($oldOption) {
                $oldOption->increment('balance', $oldPaidAmount);
            }
        } elseif ($oldOptId !== null && $newOptId !== null && $oldOptId !== $newOptId) {
            if ($oldPaidAmount > $eps) {
                $oldOption = PaymentOption::find($oldOptId);
                if ($oldOption) {
                    $oldOption->increment('balance', $oldPaidAmount);
                }
            }
            if ($newPaidAmount > $eps) {
                $paymentOption = PaymentOption::find($newOptId);
                if ($paymentOption) {
                    if ($paymentOption->balance + $eps >= $newPaidAmount) {
                        $paymentOption->decrement('balance', $newPaidAmount);
                    } else {
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['payment_option_id' => 'Insufficient balance in selected payment channel.']);
                    }
                }
            }
        } elseif ($newOptId !== null) {
            $paymentOption = PaymentOption::find($newOptId);
            if ($paymentOption) {
                $deltaToApply = $paymentDifference;
                if ($oldOptId === null && $paymentDifference <= $eps && $oldPaidAmount > $eps) {
                    $deltaToApply = $oldPaidAmount;
                }
                if ($deltaToApply > $eps) {
                    if ($paymentOption->balance + $eps >= $deltaToApply) {
                        $paymentOption->decrement('balance', $deltaToApply);
                    } else {
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['payment_option_id' => 'Insufficient balance in selected payment channel for this credit.']);
                    }
                } elseif ($deltaToApply < -$eps) {
                    $paymentOption->increment('balance', abs($deltaToApply));
                }
            }
        }

        $updateData = [
            'paid_amount' => $newPaidAmount,
            'payment_status' => $paymentStatus,
            'paid_date' => $credit->paid_date,
        ];

        try {
            $columns = Schema::getColumnListing('agent_credits');
            if (in_array('payment_option_id', $columns)) {
                $updateData['payment_option_id'] = $newOptId;
            }
        } catch (\Exception $e) {
            Log::warning('agent_credits payment_option_id: ' . $e->getMessage());
        }

        $credit->update($updateData);

        return redirect()
            ->back()
            ->with('success', 'Payment channel updated.');
    }

    /**
     * From agent credits list: pay remaining balance in one step (channel + Pay).
     * Credits the selected payment channel and marks the credit paid — same idea as agent sale channel + amount.
     */
    public function payRemaining(Request $request, int $id)
    {
        $credit = AgentCredit::findOrFail($id);

        if (! Schema::hasTable('payment_options')) {
            return redirect()
                ->route('admin.stock.agent-credits')
                ->withErrors(['error' => 'Payment channels are not configured.']);
        }

        $totalAmount = (float) $credit->total_amount;
        $oldPaid = (float) ($credit->paid_amount ?? 0);
        $eps = 0.0001;
        $remaining = max(0, $totalAmount - $oldPaid);

        if ($remaining <= $eps) {
            return redirect()
                ->route('admin.stock.agent-credits')
                ->with('info', 'This credit is already fully paid.');
        }

        $defaultWatuChannelRaw = Setting::query()->where('key', 'default_watu_channel_id')->value('value');
        $paymentOptionId = $defaultWatuChannelRaw !== null && $defaultWatuChannelRaw !== ''
            ? (int) $defaultWatuChannelRaw
            : null;
        if (! $paymentOptionId) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Choose a default Watu channel in Store settings before recording payment.']);
        }

        $opt = PaymentOption::visible()->whereKey($paymentOptionId)->first();
        if (! $opt) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Default Watu channel is invalid or hidden. Update Store settings.']);
        }

        $paidDate = now()->toDateString();

        DB::transaction(function () use ($credit, $opt, $remaining, $paymentOptionId, $totalAmount, $paidDate) {
            $opt->increment('balance', $remaining);

            $update = [
                'paid_amount' => $totalAmount,
                'payment_status' => 'paid',
                'paid_date' => $paidDate,
            ];
            if (Schema::hasColumn('agent_credits', 'payment_option_id')) {
                $update['payment_option_id'] = $paymentOptionId;
            }
            $credit->update($update);

            AgentCreditPayment::create([
                'agent_credit_id' => $credit->id,
                'payment_option_id' => $paymentOptionId,
                'amount' => $remaining,
                'paid_date' => $paidDate,
            ]);
        });

        return redirect()
            ->route('admin.stock.agent-credits')
            ->with('success', 'Payment recorded. Amount added to channel; status set to paid.');
    }

    public function updateCommission(Request $request, int $id)
    {
        $credit = AgentCredit::findOrFail($id);

        if (! Schema::hasColumn('agent_credits', 'commission_paid')) {
            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->withErrors(['error' => 'The database is missing the commission column. Run php artisan migrate.']);
        }

        $validated = $request->validate([
            'commission_paid' => 'required|numeric|min:0',
        ]);

        $newCommission = (float) $validated['commission_paid'];
        $eps = 0.0001;

        if (app(\App\Services\DefaultAgentCommissionService::class)->lineIsDisbursed('credit', (int) $credit->id)) {
            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->withErrors(['error' => 'This commission has already been disbursed and cannot be edited.']);
        }

        try {
            DB::transaction(function () use ($credit, $newCommission, $eps) {
                $credit->refresh();
                $commissionService = app(AgentCommissionExpenseService::class);

                if ($newCommission <= $eps) {
                    $commissionService->reverseForAgentCredit($credit);
                    $credit->update(['commission_paid' => $newCommission]);

                    return;
                }

                $hasBookedExpense = Schema::hasColumn('agent_credits', 'commission_expense_id')
                    && $credit->commission_expense_id;
                $amountChanged = abs((float) ($credit->commission_paid ?? 0) - $newCommission) > $eps;

                if ($hasBookedExpense && $amountChanged) {
                    $commissionService->reverseForAgentCredit($credit);
                    $credit->refresh();
                }

                $credit->update(['commission_paid' => $newCommission]);
            });
        } catch (\Throwable $e) {
            Log::error('Agent credit commission save failed: ' . $e->getMessage(), ['exception' => $e]);

            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->withErrors(['error' => 'Could not save commission. Try again or check logs.']);
        }

        $msg = $newCommission > $eps
            ? 'Commission saved. It will appear in Pay out; expense is recorded after Selcom payment completes.'
            : 'Commission cleared and any linked expense was reversed.';

        return redirect()
            ->route('admin.stock.agent-credits', $request->query())
            ->with('success', $msg);
    }

    public function update(Request $request, int $id)
    {
        $credit = AgentCredit::findOrFail($id);

        $rules = [
            'paid_date' => 'nullable|date',
            'paid_amount' => 'nullable|numeric|min:0',
            'installment_count' => 'nullable|integer|min:0',
            'installment_amount' => 'nullable|numeric|min:0',
            'first_due_date' => 'nullable|date',
            'installment_notes' => 'nullable|string|max:2000',
        ];
        if (Schema::hasColumn('agent_credits', 'installment_interval_days')) {
            $rules['installment_interval_days'] = 'nullable|integer|min:1|max:3650';
        }
        $rules['payment_option_id'] = Schema::hasTable('payment_options')
            ? 'nullable|exists:payment_options,id'
            : 'nullable';

        $validated = $request->validate($rules);

        $totalAmount = (float) $credit->total_amount;
        $oldPaidAmount = (float) ($credit->paid_amount ?? 0);
        $increment = max(0, (float) ($validated['paid_amount'] ?? 0));
        $remaining = max(0, $totalAmount - $oldPaidAmount);
        $eps = 0.0001;

        if ($increment > $remaining + $eps) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['paid_amount' => 'Pay amount cannot exceed the remaining balance.']);
        }

        $newPaidAmount = min($totalAmount, $oldPaidAmount + $increment);
        $paymentDifference = $newPaidAmount - $oldPaidAmount;

        $paymentStatus = $newPaidAmount >= $totalAmount - $eps ? 'paid' : ($newPaidAmount > $eps ? 'partial' : 'pending');

        $oldPaymentOption = $credit->payment_option_id;
        $newPaymentOptionId = $validated['payment_option_id'] ?? null;
        if ($newPaymentOptionId === '' || $newPaymentOptionId === false) {
            $newPaymentOptionId = null;
        } else {
            $newPaymentOptionId = (int) $newPaymentOptionId;
        }
        $newPaidDate = $validated['paid_date'] ?? null;

        $oldOptId = $oldPaymentOption !== null ? (int) $oldPaymentOption : null;
        $newOptId = $newPaymentOptionId;

        if ($newOptId === null && $oldOptId !== null && $oldPaidAmount > $eps) {
            $oldOption = PaymentOption::find($oldOptId);
            if ($oldOption) {
                $oldOption->increment('balance', $oldPaidAmount);
            }
        } elseif ($oldOptId !== null && $newOptId !== null && $oldOptId !== $newOptId) {
            if ($oldPaidAmount > $eps) {
                $oldOption = PaymentOption::find($oldOptId);
                if ($oldOption) {
                    $oldOption->increment('balance', $oldPaidAmount);
                }
            }
            if ($newPaidAmount > $eps) {
                $paymentOption = PaymentOption::find($newOptId);
                if ($paymentOption) {
                    if ($paymentOption->balance + $eps >= $newPaidAmount) {
                        $paymentOption->decrement('balance', $newPaidAmount);
                    } else {
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['payment_option_id' => 'Insufficient balance in selected payment channel.']);
                    }
                }
            }
        } elseif ($newOptId !== null) {
            $paymentOption = PaymentOption::find($newOptId);
            if ($paymentOption) {
                $deltaToApply = $paymentDifference;
                if ($oldOptId === null && $paymentDifference <= $eps && $oldPaidAmount > $eps) {
                    $deltaToApply = $oldPaidAmount;
                }
                if ($deltaToApply > $eps) {
                    if ($paymentOption->balance + $eps >= $deltaToApply) {
                        $paymentOption->decrement('balance', $deltaToApply);
                    } else {
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['paid_amount' => 'Insufficient balance in selected payment channel for this payment.']);
                    }
                } elseif ($deltaToApply < -$eps) {
                    $paymentOption->increment('balance', abs($deltaToApply));
                }
            }
        }

        $updateData = [
            'paid_amount' => $newPaidAmount,
            'payment_status' => $paymentStatus,
            'paid_date' => $newPaidDate ?? $credit->paid_date,
            'installment_count' => $validated['installment_count'] ?? $credit->installment_count,
            'installment_amount' => $validated['installment_amount'] ?? $credit->installment_amount,
            'first_due_date' => $validated['first_due_date'] ?? $credit->first_due_date,
            'installment_notes' => $validated['installment_notes'] ?? $credit->installment_notes,
        ];
        if (Schema::hasColumn('agent_credits', 'installment_interval_days')) {
            $updateData['installment_interval_days'] = array_key_exists('installment_interval_days', $validated)
                ? $validated['installment_interval_days']
                : $credit->installment_interval_days;
        }

        try {
            $columns = Schema::getColumnListing('agent_credits');
            if (in_array('payment_option_id', $columns)) {
                $updateData['payment_option_id'] = $newPaymentOptionId;
            }
        } catch (\Exception $e) {
            Log::warning('agent_credits payment_option_id: ' . $e->getMessage());
        }

        $credit->update($updateData);

        if ($paymentDifference > $eps) {
            try {
                AgentCreditPayment::create([
                    'agent_credit_id' => $credit->id,
                    'payment_option_id' => $newOptId,
                    'amount' => $paymentDifference,
                    'paid_date' => $newPaidDate ?? now()->toDateString(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create agent credit payment record: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('admin.stock.edit-agent-credit', $credit->id)
            ->with('success', 'Agent credit updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $credit = AgentCredit::findOrFail($id);
        DB::table('agent_credits')->where('id', $credit->id)->delete();

        return redirect()
            ->route('admin.stock.agent-credits', $request->query())
            ->with('success', 'Agent credit deleted successfully.');
    }
}
