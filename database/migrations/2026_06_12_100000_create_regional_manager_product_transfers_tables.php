<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('regional_manager_product_transfer_items');
        Schema::dropIfExists('regional_manager_product_transfers');

        Schema::create('regional_manager_product_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_admin_id');
            $table->unsignedBigInteger('to_regional_manager_id');
            $table->string('status', 32)->default('pending');
            $table->text('message')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('to_regional_manager_id');

            $table->foreign('created_by_admin_id', 'rm_pt_created_by_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_regional_manager_id', 'rm_pt_to_rm_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('decided_by', 'rm_pt_decided_by_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('regional_manager_product_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regional_manager_product_transfer_id');
            $table->unsignedBigInteger('product_list_id');
            $table->timestamps();

            $table->unique(
                ['regional_manager_product_transfer_id', 'product_list_id'],
                'rmpti_transfer_product_list_uniq'
            );

            $table->foreign('regional_manager_product_transfer_id', 'rm_pti_transfer_fk')
                ->references('id')->on('regional_manager_product_transfers')->cascadeOnDelete();
            $table->foreign('product_list_id', 'rm_pti_product_fk')
                ->references('id')->on('product_list')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_manager_product_transfer_items');
        Schema::dropIfExists('regional_manager_product_transfers');
    }
};
