<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateVariantImagesTable extends Migration
{
    public function up()
    {
        Schema::table('variant_images', function (Blueprint $table) {
            // Rendre la colonne nullable
            $table->foreignId('color_variant_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('variant_images', function (Blueprint $table) {
            $table->foreignId('color_variant_id')->nullable(false)->change();
        });
    }
}