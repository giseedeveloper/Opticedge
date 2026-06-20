<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('team_leader_product_transfer_items');
        Schema::dropIfExists('team_leader_product_transfers');

        Schema::create('team_leader_product_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_regional_manager_id');
            $table->unsignedBigInteger('to_team_leader_id');
            $table->string('status', 32)->default('pending');
            $table->text('message')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('to_team_leader_id');

            $table->foreign('from_regional_manager_id', 'tl_pt_from_rm_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_team_leader_id', 'tl_pt_to_tl_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('decided_by', 'tl_pt_decided_by_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('team_leader_product_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_leader_product_transfer_id');
            $table->unsignedBigInteger('product_list_id');
            $table->timestamps();

            $table->unique(
                ['team_leader_product_transfer_id', 'product_list_id'],
                'tlpti_transfer_product_list_uniq'
            );

            $table->foreign('team_leader_product_transfer_id', 'tl_pti_transfer_fk')
                ->references('id')->on('team_leader_product_transfers')->cascadeOnDelete();
            $table->foreign('product_list_id', 'tl_pti_product_fk')
                ->references('id')->on('product_list')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_leader_product_transfer_items');
        Schema::dropIfExists('team_leader_product_transfers');
    }
};
