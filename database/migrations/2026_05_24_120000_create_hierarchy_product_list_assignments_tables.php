<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recover from a partial run where CREATE succeeded but FK names were too long.
        Schema::dropIfExists('team_leader_product_list_assignments');
        Schema::dropIfExists('regional_manager_product_list_assignments');

        Schema::create('regional_manager_product_list_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regional_manager_id');
            $table->unsignedBigInteger('product_list_id');
            $table->timestamps();

            $table->unique('product_list_id', 'rm_pl_assign_pl_id_unique');

            $table->foreign('regional_manager_id', 'rm_pl_assign_rm_id_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('product_list_id', 'rm_pl_assign_pl_id_fk')
                ->references('id')->on('product_list')->cascadeOnDelete();
        });

        Schema::create('team_leader_product_list_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_leader_id');
            $table->unsignedBigInteger('product_list_id');
            $table->timestamps();

            $table->unique('product_list_id', 'tl_pl_assign_pl_id_unique');

            $table->foreign('team_leader_id', 'tl_pl_assign_tl_id_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('product_list_id', 'tl_pl_assign_pl_id_fk')
                ->references('id')->on('product_list')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_leader_product_list_assignments');
        Schema::dropIfExists('regional_manager_product_list_assignments');
    }
};
