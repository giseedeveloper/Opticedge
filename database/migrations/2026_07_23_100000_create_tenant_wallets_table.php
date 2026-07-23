<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-vendor (tenant) disbursement wallet. A pre-funded internal balance the
 * vendor tops up via Selcom deposit; agent-commission payouts are drawn from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_wallets');
    }
};
