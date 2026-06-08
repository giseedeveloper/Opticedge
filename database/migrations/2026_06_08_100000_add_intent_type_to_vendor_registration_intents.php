<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_registration_intents', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_registration_intents', 'intent_type')) {
                $table->string('intent_type', 32)->default('registration')->after('package_id');
            }
        });

        if (Schema::hasColumn('vendor_registration_intents', 'password')) {
            Schema::table('vendor_registration_intents', function (Blueprint $table) {
                $table->string('password')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('vendor_registration_intents', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_registration_intents', 'intent_type')) {
                $table->dropColumn('intent_type');
            }
        });
    }
};
