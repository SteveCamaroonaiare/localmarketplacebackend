<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Supprimer l'ancienne colonne
            $table->dropColumn('payment_method');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Ajouter la nouvelle avec les bonnes valeurs
            $table->enum('payment_method', [
                'orange_money',
                'mtn_momo', 
                'express_union',
                'card',
                'bank_transfer',
                'cash_on_delivery'
            ])->default('orange_money')->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['mobile_money', 'card', 'bank_transfer', 'cash'])
                ->default('mobile_money')
                ->after('total_price');
        });
    }
};