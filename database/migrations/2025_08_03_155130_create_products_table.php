<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->decimal('price', 10, 2);
    $table->decimal('original_price', 10, 2)->nullable();
    $table->string('image'); // Image principale par défaut
    $table->float('rating')->default(0);
    $table->integer('reviews')->default(0);
    $table->string('seller');
    $table->string('location');
    $table->string('badge')->nullable();
    $table->foreignId('category_id')->constrained();
    $table->integer('stock_quantity');
    $table->string('restock_frequency')->nullable();
    $table->boolean('return_policy')->default(false);
    $table->boolean('payment_on_delivery')->default(false);
    $table->boolean('has_color_variants')->default(false); // Nouveau champ
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
