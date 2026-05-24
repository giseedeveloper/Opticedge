<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('regional_manager_product_list_assignments')) {
            Schema::create('regional_manager_product_list_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('regional_manager_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_list_id')->constrained('product_list')->cascadeOnDelete();
                $table->timestamps();

                $table->unique('product_list_id');
            });
        }

        if (! Schema::hasTable('team_leader_product_list_assignments')) {
            Schema::create('team_leader_product_list_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_leader_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_list_id')->constrained('product_list')->cascadeOnDelete();
                $table->timestamps();

                $table->unique('product_list_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_leader_product_list_assignments');
        Schema::dropIfExists('regional_manager_product_list_assignments');
    }
};
