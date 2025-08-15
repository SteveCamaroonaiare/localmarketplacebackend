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

    // Vérification améliorée de l'article existant
    $existingItem = $cart->items()
        ->where('product_id', $request->product_id)
        ->when($request->color, function($query) use ($request) {
            return $query->where('color', $request->color);
        }, function($query) {
            return $query->whereNull('color');
        })
        ->when($request->size, function($query) use ($request) {
            return $query->where('size', $request->size);
        }, function($query) {
            return $query->whereNull('size');
        })
        ->first();

    if ($existingItem) {
        $existingItem->update(['quantity' => $existingItem->quantity + $request->quantity]);
    } else {
        $cart->items()->create([
            'product_id' => $request->product_id,
            'color' => $request->color,
            'size' => $request->size,
            'quantity' => $request->quantity
        ]);
    }

    return response()->json($cart->load('items.product'), 201);
}

public function show($id) {
    $category = Category::with('products')->find($id);

    if (!$category) {
        return response()->json(['message' => 'Catégorie non trouvée'], 404);
    }

    return response()->json([
        'id' => $category->id,
        'name' => $category->name,
        'products' => $category->products
    ]);
}

}
