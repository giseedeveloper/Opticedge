<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('regional_manager_device_return_items');
        Schema::dropIfExists('regional_manager_device_returns');
        Schema::dropIfExists('team_leader_device_return_items');
        Schema::dropIfExists('team_leader_device_returns');
        Schema::dropIfExists('agent_device_return_items');
        Schema::dropIfExists('agent_device_returns');

        Schema::create('agent_device_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_agent_id');
            $table->unsignedBigInteger('to_team_leader_id');
            $table->string('status', 32)->default('pending');
            $table->text('message')->nullable();
            $table->text('recipient_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('to_team_leader_id');
            $table->index('from_agent_id');

            $table->foreign('from_agent_id', 'adr_from_agent_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_team_leader_id', 'adr_to_tl_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('decided_by', 'adr_decided_by_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('agent_device_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_device_return_id');
            $table->unsignedBigInteger('product_list_id');
            $table->timestamps();

            $table->unique(
                ['agent_device_return_id', 'product_list_id'],
                'adri_return_product_list_uniq'
            );

            $table->foreign('agent_device_return_id', 'adri_return_fk')
                ->references('id')->on('agent_device_returns')->cascadeOnDelete();
            $table->foreign('product_list_id', 'adri_product_fk')
                ->references('id')->on('product_list')->cascadeOnDelete();
        });

        Schema::create('team_leader_device_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_team_leader_id');
            $table->unsignedBigInteger('to_regional_manager_id');
            $table->string('status', 32)->default('pending');
            $table->text('message')->nullable();
            $table->text('recipient_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('to_regional_manager_id');
            $table->index('from_team_leader_id');

            $table->foreign('from_team_leader_id', 'tldr_from_tl_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_regional_manager_id', 'tldr_to_rm_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('decided_by', 'tldr_decided_by_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('team_leader_device_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_leader_device_return_id');
            $table->unsignedBigInteger('product_list_id');
            $table->timestamps();

            $table->unique(
                ['team_leader_device_return_id', 'product_list_id'],
                'tldri_return_product_list_uniq'
            );

            $table->foreign('team_leader_device_return_id', 'tldri_return_fk')
                ->references('id')->on('team_leader_device_returns')->cascadeOnDelete();
            $table->foreign('product_list_id', 'tldri_product_fk')
                ->references('id')->on('product_list')->cascadeOnDelete();
        });

        Schema::create('regional_manager_device_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_regional_manager_id');
            $table->string('status', 32)->default('pending');
            $table->text('message')->nullable();
            $table->text('recipient_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('from_regional_manager_id');

            $table->foreign('from_regional_manager_id', 'rmdr_from_rm_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('decided_by', 'rmdr_decided_by_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('regional_manager_device_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regional_manager_device_return_id');
            $table->unsignedBigInteger('product_list_id');
            $table->timestamps();

            $table->unique(
                ['regional_manager_device_return_id', 'product_list_id'],
                'rmdri_return_product_list_uniq'
            );

            $table->foreign('regional_manager_device_return_id', 'rmdri_return_fk')
                ->references('id')->on('regional_manager_device_returns')->cascadeOnDelete();
            $table->foreign('product_list_id', 'rmdri_product_fk')
                ->references('id')->on('product_list')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_manager_device_return_items');
        Schema::dropIfExists('regional_manager_device_returns');
        Schema::dropIfExists('team_leader_device_return_items');
        Schema::dropIfExists('team_leader_device_returns');
        Schema::dropIfExists('agent_device_return_items');
        Schema::dropIfExists('agent_device_returns');
    }
};
