<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'team_leader_id')) {
            return;
        }

        $after = 'branch_id';
        if (Schema::hasColumn('users', 'regional_manager_id')) {
            $after = 'regional_manager_id';
        } elseif (Schema::hasColumn('users', 'region_id')) {
            $after = 'region_id';
        }

        Schema::table('users', function (Blueprint $table) use ($after) {
            $table->foreignId('team_leader_id')->nullable()->after($after)->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'team_leader_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropForeign(['team_leader_id']);
            } catch (\Throwable) {
            }
            $table->dropColumn('team_leader_id');
        });
    }
};
