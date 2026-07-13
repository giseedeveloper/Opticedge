<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'experience_bio')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('experience_bio')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'experience_bio')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('experience_bio');
            });
        }
    }
};
