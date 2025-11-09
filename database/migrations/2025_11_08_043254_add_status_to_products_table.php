<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Ajouter merchant_id si pas déjà présent
            
            
            // Ajouter le statut de validation
            if (!Schema::hasColumn('products', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            }
            
            // Ajouter raison du refus
            if (!Schema::hasColumn('products', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            
            // Ajouter date de validation
            if (!Schema::hasColumn('products', 'validated_at')) {
                $table->timestamp('validated_at')->nullable();
            }
            
            // Ajouter admin qui a validé
            if (!Schema::hasColumn('products', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['status', 'rejection_reason', 'validated_at', 'validated_by']);
             
        }); 
    }
};