<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_in_stock')->default(true)->after('stock_quantity');
        });

        // Mettre à jour les produits existants
        DB::statement('UPDATE products SET is_in_stock = (stock_quantity > 0)');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_in_stock');
        });
    }
};