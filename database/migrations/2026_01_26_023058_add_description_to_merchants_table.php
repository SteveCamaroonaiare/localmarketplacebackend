<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */// Dans une migration
public function up()
{
    Schema::table('merchants', function (Blueprint $table) {
        $table->text('description')->nullable()->after('shop_name');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
             $table->dropColumn('description');

        });
    }
};
