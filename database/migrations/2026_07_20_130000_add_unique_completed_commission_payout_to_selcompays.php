<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database-level safety net against paying the same agent commission line twice.
 *
 * The application already guards this (SelcomAgentCommissionCheckoutService +
 * AgentCommissionExpenseService), but a concurrent race could still let two
 * separate selcompay rows for the same commission line both reach "completed".
 *
 * MySQL has no partial indexes, so we add a STORED generated column that holds
 * "{type}:{id}" only for COMPLETED agent-commission rows (NULL otherwise). MySQL
 * permits many NULLs in a unique index, so failed/pending/timeout retries stay
 * allowed while a second *completed* payout for the same line is rejected.
 */
return new class extends Migration
{
    private const INDEX = 'uniq_completed_commission_payout';

    public function up(): void
    {
        // Generated-column partial-unique trick is MySQL-specific.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasColumn('selcompays', 'completed_commission_key')) {
            DB::statement(<<<'SQL'
                ALTER TABLE selcompays
                ADD COLUMN completed_commission_key VARCHAR(64)
                GENERATED ALWAYS AS (
                    CASE
                        WHEN payment_status = 'completed'
                            AND purpose = 'agent_commission_checkout'
                            AND payout_source_type IS NOT NULL
                            AND payout_source_id IS NOT NULL
                        THEN CONCAT(payout_source_type, ':', payout_source_id)
                        ELSE NULL
                    END
                ) STORED
            SQL);
        }

        if (! $this->indexExists('selcompays', self::INDEX)) {
            DB::statement('ALTER TABLE selcompays ADD UNIQUE INDEX '.self::INDEX.' (completed_commission_key)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if ($this->indexExists('selcompays', self::INDEX)) {
            DB::statement('ALTER TABLE selcompays DROP INDEX '.self::INDEX);
        }

        if (Schema::hasColumn('selcompays', 'completed_commission_key')) {
            DB::statement('ALTER TABLE selcompays DROP COLUMN completed_commission_key');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return count(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index])) > 0;
    }
};
