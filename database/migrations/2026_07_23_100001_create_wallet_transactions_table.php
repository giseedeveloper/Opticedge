<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger for every movement on a tenant disbursement wallet. Each row records the
 * direction, amount, and the resulting running balance, plus an optional reference
 * to the source (e.g. a Selcompay top-up or an agent-commission payout) so movements
 * can be applied idempotently and reconciled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            // topup | payout | payout_reversal | adjustment
            $table->string('type', 32);
            $table->string('reference_type', 48)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // One ledger row per source event → lets credit()/debit() be idempotent.
            $table->unique(['type', 'reference_type', 'reference_id'], 'uniq_wallet_txn_reference');
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
