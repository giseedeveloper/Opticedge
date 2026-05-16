<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $afterBase = Schema::hasColumn('users', 'branch_id') ? 'branch_id' : 'phone';

        Schema::table('users', function (Blueprint $table) use ($afterBase) {
            if (! Schema::hasColumn('users', 'region_id') && Schema::hasTable('regions')) {
                $table->foreignId('region_id')->nullable()->after($afterBase)->constrained('regions')->nullOnDelete();
            }

            $afterManager = Schema::hasColumn('users', 'region_id') ? 'region_id' : $afterBase;
            if (! Schema::hasColumn('users', 'regional_manager_id')) {
                $table->foreignId('regional_manager_id')->nullable()->after($afterManager)->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('users', function (Blueprint $table) use ($afterBase) {
            if (! Schema::hasColumn('users', 'notes')) {
                $afterNotes = Schema::hasColumn('users', 'regional_manager_id')
                    ? 'regional_manager_id'
                    : (Schema::hasColumn('users', 'region_id') ? 'region_id' : $afterBase);

                $table->text('notes')->nullable()->after($afterNotes);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'regional_manager_id')) {
                try {
                    $table->dropForeign(['regional_manager_id']);
                } catch (\Throwable) {
                }
            }
            if (Schema::hasColumn('users', 'region_id')) {
                try {
                    $table->dropForeign(['region_id']);
                } catch (\Throwable) {
                }
            }
            $cols = array_filter([
                Schema::hasColumn('users', 'region_id') ? 'region_id' : null,
                Schema::hasColumn('users', 'regional_manager_id') ? 'regional_manager_id' : null,
                Schema::hasColumn('users', 'notes') ? 'notes' : null,
            ]);
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
