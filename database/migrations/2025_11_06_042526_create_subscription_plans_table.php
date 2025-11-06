<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('yearly_price', 12, 2)->default(0);
            $table->integer('product_limit')->default(0); // 0 = illimité
            $table->integer('order_limit')->default(0); // 0 = illimité
            $table->decimal('commission_rate', 5, 2)->default(0); // Pourcentage
            $table->json('features')->nullable(); // Fonctionnalités incluses
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('merchant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->string('stripe_subscription_id')->nullable();
            $table->enum('status', ['active', 'canceled', 'past_due', 'unpaid', 'incomplete'])->default('active');
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->decimal('amount', 12, 2);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index('ends_at');
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('merchant_subscriptions')->onDelete('cascade');
            $table->string('payment_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('XAF');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded']);
            $table->enum('method', ['card', 'mobile_money', 'bank_transfer']);
            $table->string('transaction_reference')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('merchant_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};