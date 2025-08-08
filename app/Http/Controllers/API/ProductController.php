<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
}