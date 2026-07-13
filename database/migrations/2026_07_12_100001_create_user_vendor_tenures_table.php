<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_vendor_tenures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('role', 50);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('source', 32)->default('invitation');
            $table->foreignId('invitation_id')->nullable()->constrained('guest_vendor_invitations')->nullOnDelete();
            $table->foreignId('termination_request_id')->nullable()->constrained('contract_termination_requests')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['user_id', 'tenant_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vendor_tenures');
    }
};
