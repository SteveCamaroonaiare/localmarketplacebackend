<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_cart_items_table.php
public function up()
{
    Schema::create('cart_items', function (Blueprint $table) {
        $table->id();
               $table->string('product_name');
        $table->decimal('price', 10, 2);
        $table->decimal('original_price', 10, 2)->nullable();
        $table->string('image')->nullable();
        $table->string('size')->nullable();
        $table->string('color')->nullable();
        $table->string('seller')->nullable();
        $table->string('location')->nullable();
        $table->integer('quantity')->default(1);
        $table->foreignId('cart_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('color_variant_id')->nullable()->constrained()->onDelete('set null');
 
        $table->timestamps();
         
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
