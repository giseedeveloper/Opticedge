<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_termination_requests')) {
            return;
        }

        // Contract terminations are now admin-only; promote any stuck major queue items.
        DB::table('contract_termination_requests')
            ->where('status', 'awaiting_major')
            ->update([
                'status' => 'pending',
                'major_status' => 'skipped',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Irreversible data promotion; no-op.
    }
};
