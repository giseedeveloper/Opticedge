<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('selcompays') || Schema::hasColumn('selcompays', 'purpose')) {
            return;
        }

        Schema::table('selcompays', function (Blueprint $table) {
            $table->string('purpose', 32)->default('order_payment');
            $table->string('payout_source_type', 32)->nullable();
            $table->unsignedBigInteger('payout_source_id')->nullable();
        });

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('selcompays', function (Blueprint $table) {
            $table->dropForeign(['local_order_id']);
        });

        DB::statement('ALTER TABLE selcompays MODIFY local_order_id BIGINT UNSIGNED NULL');

        Schema::table('selcompays', function (Blueprint $table) {
            $table->foreign('local_order_id')->references('id')->on('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('selcompays') || ! Schema::hasColumn('selcompays', 'purpose')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            Schema::table('selcompays', function (Blueprint $table) {
                $table->dropColumn(['purpose', 'payout_source_type', 'payout_source_id']);
            });

            return;
        }

        DB::table('selcompays')->whereNull('local_order_id')->delete();

        Schema::table('selcompays', function (Blueprint $table) {
            $table->dropForeign(['local_order_id']);
        });

        DB::statement('ALTER TABLE selcompays MODIFY local_order_id BIGINT UNSIGNED NOT NULL');

        Schema::table('selcompays', function (Blueprint $table) {
            $table->foreign('local_order_id')->references('id')->on('orders')->onDelete('cascade');
        });

        Schema::table('selcompays', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'payout_source_type', 'payout_source_id']);
        });
    }
};
