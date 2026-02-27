<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modifier l'enum payment_method dans la table orders
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('orange_money', 'mtn_momo', 'express_union', 'card', 'bank_transfer', 'cash_on_delivery') DEFAULT 'orange_money'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('mobile_money', 'card', 'bank_transfer', 'cash') DEFAULT 'mobile_money'");
    }
};