<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchases') && ! Schema::hasColumn('purchases', 'is_passthrough')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->boolean('is_passthrough')->default(false)->after('note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'is_passthrough')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('is_passthrough');
            });
        }
    }
};
