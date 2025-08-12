<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'color' => 'nullable|string',
        'size' => 'nullable|string',
        'quantity' => 'required|integer|min:1',
    ]);

    $user = Auth::user();
    $cart = Cart::firstOrCreate(['user_id' => $user->id]);

    $product = Product::with('seller')->findOrFail($request->product_id);

    // Récupérer les données du variant si nécessaire
    $variantData = [];
    if ($request->product_variant_id) {
        $variant = ProductVariant::find($request->product_variant_id);
        $variantData = [
            'price' => $variant->price,
            'original_price' => $variant->original_price,
            'size' => $variant->size,
        ];
    }

    // Créer l'item du panier avec toutes les données nécessaires
    $cartItem = $cart->items()->create([
        'product_id' => $product->id,
        'product_variant_id' => $request->product_variant_id,
        'color_variant_id' => $request->color_variant_id,
        'product_name' => $product->name,
        'price' => $variantData['price'] ?? $product->price,
        'original_price' => $variantData['original_price'] ?? $product->original_price,
        'image' => $product->image,
        'size' => $variantData['size'] ?? $request->size,
        'color' => $request->color,
        'seller' => $product->seller->name,
        'location' => $product->seller->location,
        'quantity' => $request->quantity,
    ]);

    return response()->json($cartItem, 201);
}
}
