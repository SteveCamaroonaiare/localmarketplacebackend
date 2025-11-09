<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Rendre la colonne image nullable si elle existe
            if (Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->change();
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable(false)->change();
            }
        });
    }
};