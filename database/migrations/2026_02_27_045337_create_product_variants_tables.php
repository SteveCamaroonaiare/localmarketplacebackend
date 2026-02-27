<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table pour les variantes de produits
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique()->nullable(); // Code unique
            $table->string('color_name')->nullable();
            $table->string('color_code')->nullable(); // Code hex de la couleur
            $table->string('size_name')->nullable();
            $table->decimal('price', 10, 2); // Prix spécifique à cette variante
            $table->decimal('original_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('image_path')->nullable(); // Image principale de la variante
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            
            $table->index(['product_id', 'color_name', 'size_name']);
        });

        // Table pour les images des variantes
        Schema::create('product_variant_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->string('image_path');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_images');
        Schema::dropIfExists('product_variants');
    }
};