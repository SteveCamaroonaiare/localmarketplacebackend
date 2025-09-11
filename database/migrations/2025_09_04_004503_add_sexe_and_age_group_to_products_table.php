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
         Schema::table('products', function (Blueprint $table) {
        $table->enum('sexe', ['H', 'F'])->nullable()->comment('H=Homme, F=Femme');
        $table->enum('age_group', ['adult', 'child'])->nullable()->comment('adulte ou enfant');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
                    $table->dropColumn(['sexe', 'age_group']);

        });
    }
};
