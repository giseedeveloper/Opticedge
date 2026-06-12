<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('team_leader_product_transfers')) {
            Schema::create('team_leader_product_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_regional_manager_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('to_team_leader_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 32)->default('pending');
                $table->text('message')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index('to_team_leader_id');
            });
        }

        if (! Schema::hasTable('team_leader_product_transfer_items')) {
            Schema::create('team_leader_product_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_leader_product_transfer_id')
                    ->constrained('team_leader_product_transfers')
                    ->cascadeOnDelete();
                $table->foreignId('product_list_id')->constrained('product_list')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(
                    ['team_leader_product_transfer_id', 'product_list_id'],
                    'tlpti_transfer_product_list_uniq'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_leader_product_transfer_items');
        Schema::dropIfExists('team_leader_product_transfers');
    }
};
