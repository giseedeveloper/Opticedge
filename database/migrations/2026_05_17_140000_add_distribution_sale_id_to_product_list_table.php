<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_list')) {
            return;
        }

        Schema::table('product_list', function (Blueprint $table) {
            if (! Schema::hasColumn('product_list', 'distribution_sale_id')) {
                $table->foreignId('distribution_sale_id')
                    ->nullable()
                    ->after('agent_sale_id')
                    ->constrained('distribution_sales')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_list')) {
            return;
        }

        Schema::table('product_list', function (Blueprint $table) {
            if (Schema::hasColumn('product_list', 'distribution_sale_id')) {
                $table->dropForeign(['distribution_sale_id']);
                $table->dropColumn('distribution_sale_id');
            }
        });
    }
};
