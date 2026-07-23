<?php

namespace App\Services;

use App\Exceptions\InsufficientWalletBalanceException;
use App\Models\TenantWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * The only place tenant disbursement-wallet balances are read and mutated. Every
 * mutation locks the wallet row, records a ledger entry, and updates the running
 * balance in one transaction. Reference-tagged movements are idempotent so a
 * retried webhook/poll cannot double-apply.
 */
class WalletService
{
    private const EPS = 0.0001;

    public function wallet(int $tenantId): TenantWallet
    {
        return TenantWallet::query()->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['balance' => 0],
        );
    }

    public function balance(int $tenantId): float
    {
        return (float) ($this->wallet($tenantId)->balance);
    }

    public function hasSufficientBalance(int $tenantId, float $amount): bool
    {
        return $this->balance($tenantId) + self::EPS >= $amount;
    }

    /**
     * Add funds to a wallet. Idempotent when a (type, reference) pair is supplied:
     * a second call with the same reference returns the original transaction.
     */
    public function credit(
        int $tenantId,
        float $amount,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?int $createdBy = null,
    ): WalletTransaction {
        return $this->apply('credit', $tenantId, $amount, $type, $referenceType, $referenceId, $description, $createdBy);
    }

    /**
     * Remove funds from a wallet. Throws {@see InsufficientWalletBalanceException}
     * when the balance cannot cover the amount. Idempotent per (type, reference).
     */
    public function debit(
        int $tenantId,
        float $amount,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?int $createdBy = null,
    ): WalletTransaction {
        return $this->apply('debit', $tenantId, $amount, $type, $referenceType, $referenceId, $description, $createdBy);
    }

    /**
     * Credit back a payout that later failed or expired, once. No-op if the original
     * payout debit is missing or a reversal was already recorded.
     */
    public function reversePayout(int $selcompayId, ?string $description = null): ?WalletTransaction
    {
        $payout = WalletTransaction::query()
            ->where('type', WalletTransaction::TYPE_PAYOUT)
            ->where('reference_type', 'selcompay')
            ->where('reference_id', $selcompayId)
            ->first();

        if (! $payout) {
            return null;
        }

        return $this->credit(
            (int) $payout->tenant_id,
            (float) $payout->amount,
            WalletTransaction::TYPE_PAYOUT_REVERSAL,
            'selcompay',
            $selcompayId,
            $description ?? 'Reversal of failed/expired payout',
        );
    }

    private function apply(
        string $direction,
        int $tenantId,
        float $amount,
        string $type,
        ?string $referenceType,
        ?int $referenceId,
        ?string $description,
        ?int $createdBy,
    ): WalletTransaction {
        $amount = round($amount, 2);

        return DB::transaction(function () use ($direction, $tenantId, $amount, $type, $referenceType, $referenceId, $description, $createdBy) {
            // Idempotency: a movement already recorded for this (type, reference) wins.
            if ($referenceType !== null && $referenceId !== null) {
                $existing = WalletTransaction::query()
                    ->where('type', $type)
                    ->where('reference_type', $referenceType)
                    ->where('reference_id', $referenceId)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            // Lock the wallet row so concurrent movements can't race the balance.
            $wallet = TenantWallet::query()->where('tenant_id', $tenantId)->lockForUpdate()->first();
            if (! $wallet) {
                $wallet = TenantWallet::query()->create(['tenant_id' => $tenantId, 'balance' => 0]);
                $wallet = TenantWallet::query()->where('tenant_id', $tenantId)->lockForUpdate()->first();
            }

            $current = (float) $wallet->balance;

            if ($direction === 'debit' && $current + self::EPS < $amount) {
                throw new InsufficientWalletBalanceException(
                    $current,
                    $amount,
                    'Insufficient wallet balance.',
                );
            }

            $newBalance = round($direction === 'credit' ? $current + $amount : $current - $amount, 2);
            $wallet->balance = $newBalance;
            $wallet->save();

            return WalletTransaction::query()->create([
                'tenant_id' => $tenantId,
                'direction' => $direction,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_by' => $createdBy,
            ]);
        });
    }
}
