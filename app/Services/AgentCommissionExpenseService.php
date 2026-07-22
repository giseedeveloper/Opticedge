<?php

namespace App\Services;

use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\Expense;
use App\Models\PaymentOption;
use App\Models\Selcompay;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AgentCommissionExpenseService
{
    private const EPS = 0.0001;

    /**
     * Book expense after a Selcom commission payout completes — whether via the
     * Checkout (dev) flow or the Business disbursement (live) flow.
     */
    public function bookFromSelcompay(Selcompay $selcompay): void
    {
        if (! in_array($selcompay->purpose, [
            Selcompay::PURPOSE_AGENT_COMMISSION_CHECKOUT,
            Selcompay::PURPOSE_AGENT_COMMISSION_DISBURSE,
        ], true)) {
            return;
        }

        if ($selcompay->payment_status !== 'completed') {
            return;
        }

        if (! $selcompay->payout_source_type || ! $selcompay->payout_source_id) {
            return;
        }

        if ($selcompay->payout_source_type === 'agent_credit') {
            $credit = AgentCredit::query()->with('agent')->find($selcompay->payout_source_id);
            if ($credit) {
                $this->bookForAgentCredit($credit);
            }

            return;
        }

        if ($selcompay->payout_source_type === 'agent_sale') {
            $sale = AgentSale::query()->with('agent')->find($selcompay->payout_source_id);
            if ($sale) {
                $this->bookForAgentSale($sale);
            }
        }
    }

    /**
     * @return bool True if expense was created or already linked.
     */
    public function bookForAgentSale(AgentSale $sale): bool
    {
        if (! Schema::hasColumn('agent_sales', 'commission_expense_id')) {
            return false;
        }

        $sale->refresh();

        if ($sale->commission_expense_id) {
            return true;
        }

        $amount = (float) ($sale->commission_paid ?? 0);
        if ($amount <= self::EPS) {
            return false;
        }

        try {
            DB::transaction(function () use ($sale, $amount) {
                $sale->refresh();
                if ($sale->commission_expense_id) {
                    return;
                }

                $expenseId = $this->createExpense(
                    $amount,
                    'Agent sale commission (sale #' . $sale->id . ')'
                );

                $sale->update(['commission_expense_id' => $expenseId]);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Agent sale commission expense booking failed after Selcom', [
                'agent_sale_id' => $sale->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return bool True if expense was created or already linked.
     */
    public function bookForAgentCredit(AgentCredit $credit): bool
    {
        if (! Schema::hasColumn('agent_credits', 'commission_expense_id')) {
            return false;
        }

        $credit->refresh();

        if ($credit->commission_expense_id) {
            return true;
        }

        $amount = (float) ($credit->commission_paid ?? 0);
        if ($amount <= self::EPS) {
            return false;
        }

        try {
            DB::transaction(function () use ($credit, $amount) {
                $credit->refresh();
                if ($credit->commission_expense_id) {
                    return;
                }

                $agentName = trim((string) ($credit->agent?->name ?? 'Unknown agent'));
                $expenseId = $this->createExpense(
                    $amount,
                    'Agent commission - ' . $agentName . ' (credit #' . $credit->id . ')'
                );

                $credit->update(['commission_expense_id' => $expenseId]);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Agent credit commission expense booking failed after Selcom', [
                'agent_credit_id' => $credit->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function reverseForAgentSale(AgentSale $sale): void
    {
        if (! Schema::hasColumn('agent_sales', 'commission_expense_id')) {
            return;
        }

        DB::transaction(function () use ($sale) {
            $sale->refresh();

            $linkedExpense = null;
            if ($sale->commission_expense_id) {
                $linkedExpense = Expense::query()->lockForUpdate()->find($sale->commission_expense_id);
            }
            if (! $linkedExpense) {
                $linkedExpense = Expense::query()
                    ->lockForUpdate()
                    ->where('activity', 'Agent sale commission (sale #' . $sale->id . ')')
                    ->latest('id')
                    ->first();
            }

            if (! $linkedExpense) {
                return;
            }

            $this->deleteExpenseAndRefundChannel($linkedExpense);
            $sale->commission_expense_id = null;
            $sale->saveQuietly();
        });
    }

    public function reverseForAgentCredit(AgentCredit $credit): void
    {
        if (! Schema::hasColumn('agent_credits', 'commission_expense_id')) {
            return;
        }

        DB::transaction(function () use ($credit) {
            $credit->refresh();

            if (! $credit->commission_expense_id) {
                return;
            }

            $linkedExpense = Expense::query()->lockForUpdate()->find($credit->commission_expense_id);
            if (! $linkedExpense) {
                $credit->commission_expense_id = null;
                $credit->saveQuietly();

                return;
            }

            $this->deleteExpenseAndRefundChannel($linkedExpense);
            $credit->commission_expense_id = null;
            $credit->saveQuietly();
        });
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function createExpense(float $amount, string $activity): int
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasTable('payment_options')) {
            throw new \InvalidArgumentException('Expenses or payment channels are not configured.');
        }

        $defaultChannelRaw = Setting::query()->where('key', 'default_agent_commission_channel_id')->value('value');
        $defaultChannelId = $defaultChannelRaw !== null && $defaultChannelRaw !== '' ? (int) $defaultChannelRaw : null;

        if (! $defaultChannelId) {
            throw new \InvalidArgumentException('Choose a default commission channel in Store settings before booking commission expenses.');
        }

        $option = PaymentOption::query()
            ->visible()
            ->whereKey($defaultChannelId)
            ->lockForUpdate()
            ->first();

        if (! $option) {
            throw new \InvalidArgumentException('The default commission channel is invalid or hidden. Update Store settings.');
        }

        if ((float) $option->balance + self::EPS < $amount) {
            throw new \InvalidArgumentException('Insufficient balance in the default commission channel for this amount.');
        }

        $option->decrement('balance', $amount);

        $expense = Expense::create([
            'activity' => $activity,
            'amount' => $amount,
            'cash_used' => null,
            'payment_option_id' => $option->id,
            'date' => now()->toDateString(),
        ]);

        return (int) $expense->id;
    }

    private function deleteExpenseAndRefundChannel(Expense $expense): void
    {
        $opt = $expense->paymentOption;
        if ($opt) {
            $opt->increment('balance', (float) $expense->amount);
        }
        DB::table('expenses')->where('id', $expense->id)->delete();
    }
}
