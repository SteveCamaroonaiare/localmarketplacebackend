<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('reserved_quantity')->default(0)->after('stock_quantity');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('stock_deducted')->default(false)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('reserved_quantity');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('stock_deducted');
        });
    }
};