<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;

class MerchantPublicController extends Controller
{
    public function show(Merchant $merchant)
    {
        // Vérifier si le marchand existe
        if (!$merchant) {
            return response()->json(['error' => 'Marchand non trouvé'], 404);
        }

        return response()->json([
            'id' => $merchant->id,
            'name' => $merchant->shop_name ?? $merchant->name,
            'logo' => $merchant->logo ?? null,
            'description' => $merchant->shop_description ?? $merchant->shop_address ?? 'Boutique ' . $merchant->name,
            'rating' => $merchant->rating ?? 4.8,
            'followers' => $merchant->followers()->count(),
            'products' => $merchant->products()
                ->where('status', 'approved')
                ->select('id', 'name', 'price', 'image')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => number_format($product->price, 2, '.', ''),
                        'image' => $product->image ? asset('storage/' . $product->image) : null,
                    ];
                }),
        ]);
    }
}