<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_termination_requests')) {
            return;
        }

        Schema::table('contract_termination_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_termination_requests', 'major_user_id')) {
                $table->foreignId('major_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('contract_termination_requests', 'major_status')) {
                $table->string('major_status', 32)->nullable()->after('status');
            }
            if (! Schema::hasColumn('contract_termination_requests', 'major_note')) {
                $table->text('major_note')->nullable()->after('admin_note');
            }
            if (! Schema::hasColumn('contract_termination_requests', 'major_decided_at')) {
                $table->timestamp('major_decided_at')->nullable()->after('major_note');
            }
            if (! Schema::hasColumn('contract_termination_requests', 'major_decided_by')) {
                $table->foreignId('major_decided_by')->nullable()->after('major_decided_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('contract_termination_requests', 'force_initiated')) {
                $table->boolean('force_initiated')->default(false)->after('snapshot');
            }
        });

        // Existing pending rows were already admin-visible; keep them as pending without major gate.
        DB::table('contract_termination_requests')
            ->where('status', 'pending')
            ->whereNull('major_status')
            ->update(['major_status' => 'skipped']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('contract_termination_requests')) {
            return;
        }

        Schema::table('contract_termination_requests', function (Blueprint $table) {
            if (Schema::hasColumn('contract_termination_requests', 'major_decided_by')) {
                $table->dropConstrainedForeignId('major_decided_by');
            }
            if (Schema::hasColumn('contract_termination_requests', 'major_user_id')) {
                $table->dropConstrainedForeignId('major_user_id');
            }
            foreach (['major_status', 'major_note', 'major_decided_at', 'force_initiated'] as $col) {
                if (Schema::hasColumn('contract_termination_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
