<?php

namespace App\Http\Controllers\API;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    // Récupérer toutes les catégories avec sous-catégories
    public function index()
    {
        $categories = Category::with(['subCategories' => function($query) {
            $query->withCount('products');
        }])->withCount('products')->get();

        return response()->json($categories);
    }

    // Récupérer une catégorie spécifique avec ses sous-catégories
    public function show($id)
    {
        $category = Category::with(['subCategories' => function($query) {
            $query->withCount('products');
        }])->find($id);

        if (!$category) {
            return response()->json(['error' => 'Catégorie non trouvée'], 404);
        }

        return response()->json($category);
    }

    // Récupérer les produits par catégorie (rétrocompatible)
    public function byCategory($categoryId)
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return response()->json(['message' => 'Catégorie non trouvée'], 404);
        }

        $products = Product::with(['colorVariants', 'images'])
            ->where('category_id', $categoryId)
            ->get();

        return response()->json([
            'category' => $category,
            'products' => $products
        ]);
    }

    // NOUVEAU: Récupérer les produits par sous-catégorie
  public function bySubCategory($subCategoryId)
{
    try {
        \Log::info("Tentative de récupération de sous-catégorie: " . $subCategoryId);
        
        $subCategory = SubCategory::with('category')->find($subCategoryId);

        if (!$subCategory) {
            \Log::warning("Sous-catégorie non trouvée: " . $subCategoryId);
            return response()->json(['message' => 'Sous-catégorie non trouvée'], 404);
        }

        \Log::info("Sous-catégorie trouvée: " . $subCategory->name);
        
        $products = Product::with(['colorVariants', 'images'])
            ->where('sub_category_id', $subCategoryId)
            ->get();

        \Log::info("Produits trouvés: " . $products->count());
        
        return response()->json([
            'sub_category' => $subCategory,
            'category' => $subCategory->category,
            'products' => $products
        ]);

    } catch (\Exception $e) {
        \Log::error("Erreur dans bySubCategory: " . $e->getMessage());
        \Log::error("Stack trace: " . $e->getTraceAsString());
        
        return response()->json([
            'message' => 'Erreur interne du serveur',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function statistics()
{
    $categories = Category::withCount(['subCategories', 'products'])->get();
    
    $totalCategories = $categories->count();
    $totalSubCategories = $categories->sum('sub_categories_count');
    $totalProducts = $categories->sum('products_count');
    
    return response()->json([
        'total_categories' => $totalCategories,
        'total_sub_categories' => $totalSubCategories,
        'total_products' => $totalProducts,
        'categories' => $categories
    ]);
}
}