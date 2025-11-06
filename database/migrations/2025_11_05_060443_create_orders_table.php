<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Références
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Client
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade'); // Vendeur

            // Identifiant unique
            $table->string('order_number')->unique(); // Exemple : ORD-2025-0001

            // Statut de la commande
            $table->enum('status', [
                'pending', 'confirmed', 'processing', 'shipped',
                'delivered', 'cancelled', 'refunded'
            ])->default('pending');

            // Infos client
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');

            // Adresse de livraison
            $table->text('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_country')->default('Cameroun');

            // Livraison gérée par le vendeur
            $table->enum('delivery_type', ['merchant', 'platform'])->default('merchant');
            $table->enum('delivery_status', ['pending', 'in_progress', 'delivered', 'cancelled'])->default('pending');
            $table->string('delivery_person')->nullable();
            $table->text('delivery_notes')->nullable();

            // Montants
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2);

            // Paiement
            $table->enum('payment_method', ['cash', 'mobile_money', 'bank_transfer', 'card'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Notes diverses
            $table->text('customer_notes')->nullable();
            $table->text('merchant_notes')->nullable();

            // Suivi
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Index
            $table->index(['order_number', 'status', 'user_id', 'merchant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
