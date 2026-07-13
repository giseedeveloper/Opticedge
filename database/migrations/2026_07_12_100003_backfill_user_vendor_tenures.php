<?php

use App\Services\WorkerReputationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_vendor_tenures')) {
            return;
        }

        app(WorkerReputationService::class)->backfillTenures();
    }

    public function down(): void
    {
        // Backfill is not reversed; tenures created later remain.
    }
};
