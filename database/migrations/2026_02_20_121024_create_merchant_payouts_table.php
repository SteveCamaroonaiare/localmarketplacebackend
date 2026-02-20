<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->integer('month'); // 1-12
            $table->integer('year'); // 2024, 2025, etc.
            
            // Montants détaillés
            $table->decimal('total_sales', 12, 2); // Ventes totales du merchant
            $table->decimal('platform_commission', 12, 2); // Commission prélevée
            $table->decimal('subscription_fee', 12, 2); // Frais d'abonnement
            $table->decimal('gross_amount', 12, 2); // Montant brut
            $table->decimal('net_amount', 12, 2); // Montant net à payer
            
            // Infos paiement
            $table->enum('payment_method', [
                'orange_money',
                'mtn_momo',
                'express_union',
                'bank_transfer',
                'cash',
                'other'
            ]);
            $table->string('payment_reference')->nullable(); // Numéro de transaction
            $table->string('recipient_phone')->nullable(); // Numéro Mobile Money
            $table->string('recipient_account')->nullable(); // IBAN ou autre
            $table->text('notes')->nullable();
            
            // Statut et suivi
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users'); // Super admin
            
            $table->timestamps();
            
            // Index et contraintes
            $table->unique(['merchant_id', 'month', 'year']);
            $table->index('status');
            $table->index(['month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_payouts');
    }
};