<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // null = unlimited
            $table->unsignedInteger('max_agents')->nullable()->after('max_users');
            $table->unsignedInteger('max_admins')->nullable()->after('max_agents');
            $table->unsignedInteger('trial_days')->nullable()->after('max_admins');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['max_agents', 'max_admins', 'trial_days']);
        });
    }
};
