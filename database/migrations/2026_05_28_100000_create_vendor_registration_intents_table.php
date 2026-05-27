<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_registration_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('vendor_name');
            $table->string('brand_name')->nullable();
            $table->string('slug')->nullable();
            $table->string('admin_name');
            $table->string('email');
            $table->string('phone', 32);
            $table->string('password');
            $table->string('payment_phone', 32)->nullable();
            $table->string('status', 32)->default('draft');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_registration_intents');
    }
};
