<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_updates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->onDelete('cascade');

            $table->json('old_data'); // anciennes valeurs
            $table->json('new_data'); // nouvelles valeurs

            $table->json('old_images')->nullable();
            $table->json('new_images')->nullable();

            $table->string('status')->default('pending'); 
            // pending | approved | rejected

            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_updates');
    }
};
