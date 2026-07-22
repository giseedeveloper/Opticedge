<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the double-payout DB safety net (see the 2026_07_20 migration) to cover
 * the Selcom Business disbursement flow (purpose 'agent_commission_disburse'),
 * where paying twice sends REAL money twice.
 *
 * The generated key is now prefixed with the purpose so each payout method is
 * guarded independently: a second *completed* payout for the same commission line
 * via the same method is rejected, while failed/pending/timeout retries (NULL key)
 * stay allowed.
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

        $this->rebuild(
            "CASE
                WHEN payment_status = 'completed'
                    AND purpose IN ('agent_commission_checkout', 'agent_commission_disburse')
                    AND payout_source_type IS NOT NULL
                    AND payout_source_id IS NOT NULL
                THEN CONCAT(purpose, ':', payout_source_type, ':', payout_source_id)
                ELSE NULL
            END",
            128
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Restore the original checkout-only guard.
        $this->rebuild(
            "CASE
                WHEN payment_status = 'completed'
                    AND purpose = 'agent_commission_checkout'
                    AND payout_source_type IS NOT NULL
                    AND payout_source_id IS NOT NULL
                THEN CONCAT(payout_source_type, ':', payout_source_id)
                ELSE NULL
            END",
            64
        );
    }

    private function rebuild(string $expression, int $length): void
    {
        if ($this->indexExists('selcompays', self::INDEX)) {
            DB::statement('ALTER TABLE selcompays DROP INDEX '.self::INDEX);
        }

        if (Schema::hasColumn('selcompays', 'completed_commission_key')) {
            DB::statement('ALTER TABLE selcompays DROP COLUMN completed_commission_key');
        }

        DB::statement(
            "ALTER TABLE selcompays
             ADD COLUMN completed_commission_key VARCHAR({$length})
             GENERATED ALWAYS AS ({$expression}) STORED"
        );

        DB::statement('ALTER TABLE selcompays ADD UNIQUE INDEX '.self::INDEX.' (completed_commission_key)');
    }

    private function indexExists(string $table, string $index): bool
    {
        return count(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index])) > 0;
    }
};
