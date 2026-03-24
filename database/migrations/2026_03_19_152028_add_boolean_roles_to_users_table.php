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
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('is_customer')->default(true)->after('email');
        $table->boolean('is_merchant')->default(false)->after('is_customer');
        
        // Si tu veux supprimer l'ancienne colonne 'role' (enum)
        $table->dropColumn('role'); 
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->enum('role', ['client', 'merchant'])->default('client');
        $table->dropColumn(['is_customer', 'is_merchant']);
    });
}

};
