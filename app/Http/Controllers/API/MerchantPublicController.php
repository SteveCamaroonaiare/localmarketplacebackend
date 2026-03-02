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

        // Récupérer les produits avec leurs images
        $products = $merchant->products()
            ->where('status', 'approved')
            ->with('images')
            ->get()
            ->map(function ($product) {
                // Récupérer la première image
                $firstImage = $product->images->first();
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'original_price' => $product->original_price,
                    'min_price' => $product->min_price,
                    'max_price' => $product->max_price,
                    'image' => $firstImage 
                        ? asset('storage/' . $firstImage->image_path)
                        : ($product->image ? asset('storage/' . $product->image) : null),
                    'images' => $product->images->map(function($img) {
                        return [
                            'id' => $img->id,
                            'image_path' => $img->image_path,
                            'url' => asset('storage/' . $img->image_path)
                        ];
                    }),
                ];
            });

        return response()->json([
            'id' => $merchant->id,
            'name' => $merchant->shop_name ?? $merchant->name,
            'logo' => $merchant->logo ? asset('storage/' . $merchant->logo) : null,
            'description' => $merchant->shop_description ?? $merchant->shop_address ?? 'Boutique ' . $merchant->name,
            'rating' => $merchant->rating ?? 4.8,
            'followers' => $merchant->followers()->count(),
            'products' => $products,
        ]);
    }
}