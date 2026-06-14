<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_device_returns')) {
            Schema::create('agent_device_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_agent_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('to_team_leader_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 32)->default('pending');
                $table->text('message')->nullable();
                $table->text('recipient_note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index('to_team_leader_id');
                $table->index('from_agent_id');
            });
        }

        if (! Schema::hasTable('agent_device_return_items')) {
            Schema::create('agent_device_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agent_device_return_id')
                    ->constrained('agent_device_returns')
                    ->cascadeOnDelete();
                $table->foreignId('product_list_id')->constrained('product_list')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(
                    ['agent_device_return_id', 'product_list_id'],
                    'adri_return_product_list_uniq'
                );
            });
        }

        if (! Schema::hasTable('team_leader_device_returns')) {
            Schema::create('team_leader_device_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_team_leader_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('to_regional_manager_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 32)->default('pending');
                $table->text('message')->nullable();
                $table->text('recipient_note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index('to_regional_manager_id');
                $table->index('from_team_leader_id');
            });
        }

        if (! Schema::hasTable('team_leader_device_return_items')) {
            Schema::create('team_leader_device_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_leader_device_return_id')
                    ->constrained('team_leader_device_returns')
                    ->cascadeOnDelete();
                $table->foreignId('product_list_id')->constrained('product_list')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(
                    ['team_leader_device_return_id', 'product_list_id'],
                    'tldri_return_product_list_uniq'
                );
            });
        }

        if (! Schema::hasTable('regional_manager_device_returns')) {
            Schema::create('regional_manager_device_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_regional_manager_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 32)->default('pending');
                $table->text('message')->nullable();
                $table->text('recipient_note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index('from_regional_manager_id');
            });
        }

        if (! Schema::hasTable('regional_manager_device_return_items')) {
            Schema::create('regional_manager_device_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('regional_manager_device_return_id')
                    ->constrained('regional_manager_device_returns')
                    ->cascadeOnDelete();
                $table->foreignId('product_list_id')->constrained('product_list')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(
                    ['regional_manager_device_return_id', 'product_list_id'],
                    'rmdri_return_product_list_uniq'
                );
            });
        }
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
