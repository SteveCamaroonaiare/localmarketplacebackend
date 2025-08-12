<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ColorVariant;
use App\Models\Size;

class ProductController extends Controller
{
    // Récupérer tous les produits
    public function index()
    {
        $products = Product::with('variants', 'category')->get();
        return response()->json($products);
    }

    // Récupérer un produit spécifique
    public function show($id)
    {
        $product = Product::with([
                'colorVariants.sizes',
                'colorVariants.images',
                'sizes',
                'images'])->find($id);
        
        if (!$product) {
            return response()->json(['error' => 'Produit non trouvé'], 404);
        }

        return response()->json($product);
    }

    // Récupérer les produits par catégorie
    public function byCategory($categoryId)
    {
        $products = Product::with('variants')
            ->where('category_id', $categoryId)
            ->get();

        return response()->json($products);
    }

   // ProductController.php
   public function updateStock(Request $request, $id)
{
    try {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'color_variant_id' => 'nullable|integer|exists:color_variants,id',
            'size_id' => 'nullable|integer|exists:sizes,id'
        ]);

        $quantity = $request->input('quantity');

        // 1. Décrémenter la taille si spécifiée
        if ($request->has('size_id')) {
            $size = Size::findOrFail($request->input('size_id'));
            
            if ($size->stock_quantity < $quantity) {
                return response()->json(['error' => 'Stock insuffisant pour cette taille'], 400);
            }

            $size->stock_quantity -= $quantity;
            $size->save();
            return response()->json(['message' => 'Stock de taille mis à jour']);
        }

        // 2. Décrémenter la couleur si spécifiée
        if ($request->has('color_variant_id')) {
            $color = ColorVariant::findOrFail($request->input('color_variant_id'));
            
            if ($color->stock_quantity < $quantity) {
                return response()->json(['error' => 'Stock insuffisant pour cette couleur'], 400);
            }

            $color->stock_quantity -= $quantity;
            $color->save();
            return response()->json(['message' => 'Stock de couleur mis à jour']);
        }

        // 3. Décrémenter le stock global
        $product = Product::findOrFail($id);
        
        if ($product->stock_quantity < $quantity) {
            return response()->json(['error' => 'Stock global insuffisant'], 400);
        }

        $product->stock_quantity -= $quantity;
        $product->save();

        return response()->json(['message' => 'Stock global mis à jour']);

    } catch (\Exception $e) {
        \Log::error('Erreur mise à jour stock: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur interne du serveur'], 500);
    }
}


   public function restoreStock(Request $request)
{
    $request->validate([
        'product_id' => 'required|integer',
        'quantity' => 'required|integer|min:1',
        'color_variant_id' => 'nullable|integer',
        'size_id' => 'nullable|integer'
    ]);

    $quantity = $request->input('quantity');

    // 1. Restaurer la taille si spécifiée
    if ($request->has('size_id')) {
        $size = Size::find($request->input('size_id'));
        if ($size) {
            $size->stock_quantity += $quantity;
            $size->save();
            return response()->json(['message' => 'Stock de taille restauré']);
        }
    }

    // ... même logique pour couleur et produit global
    
    if($request-> has('color_variant_id')){
        $color= ColorVariant::find($request->input('color_variants_id'));
        if($color){
            $color->stock_quantity += $quantity;
            $color->save();
            return response()->json(['message' => 'Stock de taille restauré']);
        }


    }


    return response()->json(['message' => 'Stock restauré']);
}
}