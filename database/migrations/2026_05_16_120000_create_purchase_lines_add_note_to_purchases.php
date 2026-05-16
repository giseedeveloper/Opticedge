<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('models')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('sell_price', 15, 2)->nullable();
            $table->unsignedInteger('limit_remaining');
            $table->timestamps();

            $table->unique(['purchase_id', 'product_id']);
        });

        if (Schema::hasTable('purchases') && ! Schema::hasColumn('purchases', 'note')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->text('note')->nullable()->after('sell_price');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_lines');

        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'note')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('note');
            });
        }
    }
};
