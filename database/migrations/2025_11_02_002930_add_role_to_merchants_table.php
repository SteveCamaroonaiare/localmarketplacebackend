<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('merchants', function (Blueprint $table) {
        $table->string('role')->default('merchant'); // par défaut vendeur
    });
}

public function down()
{
    Schema::table('merchants', function (Blueprint $table) {
        $table->dropColumn('role');
    });
}
};
