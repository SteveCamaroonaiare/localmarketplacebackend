<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function createShop(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'shop_name' => 'required|string|max:255',
            'shop_address' => 'nullable|string',
                            'country'=> 'required|string',

            'category' => 'required|string',
            'payment_method' => 'required|string',
            'payment_account' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Mettre à jour l'utilisateur avec les infos de la boutique
        $user->update([
            'shop_name' => $request->shop_name,
            'shop_address' => $request->shop_address,
            'country' => $request->country,

            'shop_category' => $request->category,
            'payment_method' => $request->payment_method,
            'payment_account' => $request->payment_account,
            'merchant_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Boutique créée avec succès',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_customer' => $user->is_customer,
                'is_merchant' => $user->is_merchant,
                'has_shop' => $user->hasShop(),
                'shop_name' => $user->shop_name,
            ]
        ]);
    }
}