<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('distribution_sales') || Schema::hasColumn('distribution_sales', 'invoice_number')) {
            return;
        }

        Schema::table('distribution_sales', function (Blueprint $table) {
            $table->string('invoice_number', 32)->nullable()->after('id');
            $table->unique('invoice_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('distribution_sales') || ! Schema::hasColumn('distribution_sales', 'invoice_number')) {
            return;
        }

        Schema::table('distribution_sales', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn('invoice_number');
        });
    }
};
