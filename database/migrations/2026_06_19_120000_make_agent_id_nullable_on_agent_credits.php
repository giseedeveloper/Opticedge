<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Team leader credit sales set team_leader_id and leave agent_id empty.
     */
    public function up(): void
    {
        if (! Schema::hasTable('agent_credits') || ! Schema::hasColumn('agent_credits', 'agent_id')) {
            return;
        }

        DB::statement('ALTER TABLE agent_credits MODIFY agent_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('agent_credits') || ! Schema::hasColumn('agent_credits', 'agent_id')) {
            return;
        }

        DB::statement('ALTER TABLE agent_credits MODIFY agent_id BIGINT UNSIGNED NOT NULL');
    }
};
