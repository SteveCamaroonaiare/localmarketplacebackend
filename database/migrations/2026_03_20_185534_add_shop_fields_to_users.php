<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('shop_name')->nullable()->after('is_merchant');
            $table->string('shop_address')->nullable()->after('shop_name');
            $table->string('shop_logo')->nullable()->after('shop_address');
            $table->string('shop_category')->nullable()->after('shop_logo');
            $table->string('payment_method')->nullable()->after('shop_category');
            $table->string('payment_account')->nullable()->after('payment_method');
            $table->string('merchant_status')->default('pending')->after('payment_account');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'shop_name',
                'shop_address',
                'shop_logo',
                'shop_category',
                'payment_method',
                'payment_account',
                'merchant_status',
            ]);
        });
    }
};