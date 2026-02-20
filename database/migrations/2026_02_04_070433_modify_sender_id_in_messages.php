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
    Schema::table('messages', function (Blueprint $table) {
        // Supprimer l'ancienne contrainte
        $table->dropForeign(['sender_id']);
        
        // Recréer sans contrainte spécifique
        $table->unsignedBigInteger('sender_id')->change();
    });
}

public function down()
{
    Schema::table('messages', function (Blueprint $table) {
        $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
    });
}
};
