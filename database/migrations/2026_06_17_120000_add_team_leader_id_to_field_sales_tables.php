<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_sales') && ! Schema::hasColumn('agent_sales', 'team_leader_id')) {
            Schema::table('agent_sales', function (Blueprint $table) {
                $table->foreignId('team_leader_id')->nullable()->after('agent_id')->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('agent_credits') && ! Schema::hasColumn('agent_credits', 'team_leader_id')) {
            Schema::table('agent_credits', function (Blueprint $table) {
                $table->foreignId('team_leader_id')->nullable()->after('agent_id')->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('customer_needs')) {
            Schema::table('customer_needs', function (Blueprint $table) {
                if (! Schema::hasColumn('customer_needs', 'team_leader_id')) {
                    $table->foreignId('team_leader_id')->nullable()->after('agent_id')->constrained('users')->nullOnDelete();
                }
            });

            if (Schema::hasColumn('customer_needs', 'agent_id')) {
                Schema::table('customer_needs', function (Blueprint $table) {
                    $table->unsignedBigInteger('agent_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agent_sales') && Schema::hasColumn('agent_sales', 'team_leader_id')) {
            Schema::table('agent_sales', function (Blueprint $table) {
                $table->dropConstrainedForeignId('team_leader_id');
            });
        }

        if (Schema::hasTable('agent_credits') && Schema::hasColumn('agent_credits', 'team_leader_id')) {
            Schema::table('agent_credits', function (Blueprint $table) {
                $table->dropConstrainedForeignId('team_leader_id');
            });
        }

        if (Schema::hasTable('customer_needs') && Schema::hasColumn('customer_needs', 'team_leader_id')) {
            Schema::table('customer_needs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('team_leader_id');
            });
        }
    }
};
