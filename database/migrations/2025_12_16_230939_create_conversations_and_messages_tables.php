<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Table des conversations
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Index pour performance
            $table->index(['customer_id', 'merchant_id']);
            $table->index('order_id');
        });

        // Table des messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->string('attachment_url')->nullable();
            $table->string('attachment_type')->nullable(); // image, pdf, etc.
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Index pour performance
            $table->index('conversation_id');
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};