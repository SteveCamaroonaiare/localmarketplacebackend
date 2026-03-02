<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer les anciennes tables si elles existent
        Schema::dropIfExists('product_variant_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_sizes');
        Schema::dropIfExists('product_color_variants');

        // Ajouter sort_order à product_images s'il manque
        if (!Schema::hasColumn('product_images', 'sort_order')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('image_path');
            });
        }

        // Table pour les variantes (une variante = une image)
        Schema::create('product_image_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('image_id')->constrained('product_images')->onDelete('cascade');
            $table->string('color_name')->nullable();
            $table->string('color_code')->nullable(); // hex code
            $table->decimal('price', 10, 2); // Prix pour cette image/couleur
            $table->decimal('original_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('image_id');
        });

        // Table pour les tailles disponibles pour chaque variante d'image
        Schema::create('product_image_variant_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_image_variants')->onDelete('cascade');
            $table->string('size_name'); // M, L, XL, 42, 43, etc.
            $table->integer('stock_quantity')->default(0);
            $table->timestamps();
            
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_image_variant_sizes');
        Schema::dropIfExists('product_image_variants');
    }
};