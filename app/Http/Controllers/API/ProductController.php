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
    // Récupérer tous les produits APPROUVÉS uniquement
    public function index()
    {
        $products = Product::with('variants', 'category', 'merchant')
            ->where('status', 'approved') // ⚠️ IMPORTANT : Filtrer uniquement les produits approuvés
            ->get();
        
        return response()->json($products);
    }

    // Récupérer un produit spécifique
    public function show($id)
    {
        $product = Product::with([
                'colorVariants.sizes',
                'colorVariants.images',
                'sizes',
                'images',
                'merchant'])
            ->where('status', 'approved') // ⚠️ IMPORTANT
            ->find($id);
        
        if (!$product) {
            return response()->json(['error' => 'Produit non trouvé ou non disponible'], 404);
        }

        return response()->json($product);
    }

    // Récupérer les produits par catégorie
    public function byCategory($categoryId)
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return response()->json(['message' => 'Catégorie non trouvée'], 404);
        }

        $products = Product::with('variants', 'merchant')
            ->where('category_id', $categoryId)
            ->where('status', 'approved') // ⚠️ IMPORTANT
            ->get();

        return response()->json([
            'category' => $category,
            'products' => $products
        ]);
    }

    // Produits en vedette / populaires
    public function featured()
    {
        $products = Product::with(['subCategory', 'category', 'colorVariants', 'images', 'merchant'])
            ->where('status', 'approved') // ⚠️ IMPORTANT
            ->where('rating', '>=', 4.0)
            ->take(20)
            ->get();

        return response()->json($products);
    }

    // Produits en promotion
    public function promotionalProducts()
    {
        $products = Product::with('variants', 'category', 'merchant')
            ->where('status', 'approved') // ⚠️ IMPORTANT
            ->whereColumn('price', '<', 'original_price')
            ->get()
            ->filter(function ($product) {
                $discount = (($product->original_price - $product->price) / $product->original_price) * 100;
                return $discount >= 10 && $discount <= 70;
            })
            ->values();

        return response()->json($products);
    }

    // Produits similaires
    public function similar($productId)
    {
        $product = Product::where('status', 'approved')->find($productId);
        
        if (!$product) {
            return response()->json(['error' => 'Produit non trouvé'], 404);
        }

        $similarProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $productId)
            ->where('status', 'approved') // ⚠️ IMPORTANT
            ->with(['variants.images', 'merchant'])
            ->take(9)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                    'originalPrice' => $p->original_price,
                    'rating' => $p->rating ?? 4.5,
                    'reviews' => $p->reviews_count ?? 100,
                    'seller' => $p->merchant->shop_name ?? 'Vendeur inconnu',
                    'location' => $p->merchant->country ?? 'Maroc',
                    'image' => $p->variants->first()?->images->first()?->url ?? '/placeholder.svg',
                    'badge' => $p->badge ?? null,
                ];
            });

        return response()->json($similarProducts);
    }

    // Reste des méthodes (updateStock, restoreStock) - inchangées
    public function updateStock(Request $request, $id)
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
                'color_variant_id' => 'nullable|integer|exists:color_variants,id',
                'size_id' => 'nullable|integer|exists:sizes,id'
            ]);

            $quantity = $request->input('quantity');

            if ($request->has('size_id')) {
                $size = Size::findOrFail($request->input('size_id'));
                
                if ($size->stock_quantity < $quantity) {
                    return response()->json(['error' => 'Stock insuffisant pour cette taille'], 400);
                }

                $size->stock_quantity -= $quantity;
                $size->save();
                return response()->json(['message' => 'Stock de taille mis à jour']);
            }

            if ($request->has('color_variant_id')) {
                $color = ColorVariant::findOrFail($request->input('color_variant_id'));
                
                if ($color->stock_quantity < $quantity) {
                    return response()->json(['error' => 'Stock insuffisant pour cette couleur'], 400);
                }

                $color->stock_quantity -= $quantity;
                $color->save();
                return response()->json(['message' => 'Stock de couleur mis à jour']);
            }

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

        if ($request->has('size_id')) {
            $size = Size::find($request->input('size_id'));
            if ($size) {
                $size->stock_quantity += $quantity;
                $size->save();
                return response()->json(['message' => 'Stock de taille restauré']);
            }
        }

        if($request->has('color_variant_id')){
            $color = ColorVariant::find($request->input('color_variant_id'));
            if($color){
                $color->stock_quantity += $quantity;
                $color->save();
                return response()->json(['message' => 'Stock de couleur restauré']);
            }
        }

        return response()->json(['message' => 'Stock restauré']);
    }

// app/Http/Controllers/API/ProductController.php
public function promotions()
{
    // Récupère uniquement les produits ayant une réduction entre 10% et 70%
    $products = Product::whereRaw('(original_price - price) / original_price * 100 BETWEEN 10 AND 70')
                       ->get();

    return response()->json($products);
}

}