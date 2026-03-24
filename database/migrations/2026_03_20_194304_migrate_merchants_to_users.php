<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrer les données de merchants vers users
        $merchants = DB::table('merchants')->get();
        
        foreach ($merchants as $merchant) {
            DB::table('users')
                ->where('id', $merchant->user_id)
                ->update([
                    'shop_name' => $merchant->shop_name,
                    'shop_address' => $merchant->shop_address,
                    'shop_logo' => $merchant->logo,
                    'shop_category' => $merchant->category,
                    'payment_method' => $merchant->payment_method,
                    'payment_account' => $merchant->payment_account,
                    'merchant_status' => $merchant->status ?? 'pending',
                            'country' => $merchant->country ?? 'Yaoundé'
                ]);
        }
    }

    public function down(): void
    {
        // rien 
    }
};