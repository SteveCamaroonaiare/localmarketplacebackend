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
public function index(Request $request)
{
    try {
        $user = $request->user(); // Utilisateur connecté ou null
        
        $products = Product::with(['department', 'merchant.followers', 'images'])
            ->where('status', 'approved')
            ->get()
            ->map(function ($product) use ($user) {
                $primaryImage = $product->images()->where('is_primary', true)->first();
                $imageUrl = $primaryImage 
                    ? asset('storage/' . $primaryImage->image_path)
                    : ($product->images->first() 
                        ? asset('storage/' . $product->images->first()->image_path)
                        : null);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'original_price' => $product->original_price,
                    'image' => $imageUrl,
//'badge' => $product->badge ?? $this->generateBadge($product),                        
                    'rating' => $product->rating,
                    'reviews' => $product->reviews,
                    'merchant_id' => $product->merchant_id,
                    'seller' => $product->merchant ? ($product->merchant->shop_name ?? $product->merchant->name) : null,
                    'location' => $product->location ?? $product->merchant->shop_address ?? null,
                    'department_slug' => optional($product->department)->slug,
                    'is_following' => $user && $product->merchant 
                        ? $product->merchant->isFollowedBy($user) 
                        : false,
                    'followers_count' => $product->merchant ? $product->merchant->followers_count : 0,
                ];
            });

        return response()->json($products);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors du chargement des produits',
            'message' => $e->getMessage()
        ], 500);
    }
}
    
    /**
     * Produits par département
     */
    public function byDepartment($slug)
    {
        try {
            if ($slug === 'all') {
                $products = Product::with('department')->get();
            } else {
                $department = Department::where('slug', $slug)->first();
                
                if (!$department) {
                    return response()->json(['error' => 'Département non trouvé'], 404);
                }
                
                $products = Product::with('department')
                    ->where('department_id', $department->id)
                    ->get();
            }
            
            $formatted = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'original_price' => $product->original_price,
                    'image' => $product->image,
                    'badge' => $product->badge,
                    'rating' => $product->rating,
                    'reviews' => $product->reviews,
                    'seller' => $product->seller,
                    'location' => $product->location,
                    'department_slug' => $product->department ? $product->department->slug : null,
                ];
            });
            
            return response()->json($formatted);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // Helper pour obtenir l'image principale du produit
    private function getProductImage($product)
    {
        // Priorité 1: Image principale
        if ($product->image) {
            return url('storage/' . $product->image);
        }
        
        // Priorité 2: Première image de la galerie
        if ($product->images && $product->images->count() > 0) {
            $primaryImage = $product->images->where('is_primary', true)->first() 
                         ?? $product->images->first();
            return url('storage/' . $primaryImage->image_path);
        }
        
        // Priorité 3: Image par défaut
        return '/placeholder.svg';
    }

    // Helper pour obtenir le badge du produit
    private function getProductBadge($product)
    {
        if (!$product->original_price || $product->price >= $product->original_price) {
            return null;
        }
        
        $discount = round((($product->original_price - $product->price) / $product->original_price) * 100);
        
        if ($discount >= 50) return '-' . $discount . '%';
        if ($discount >= 20) return 'PROMO';
        
        return null;
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

    // Produits vedettes pour la page d'accueil
    public function featuredProducts()
    {
        $products = Product::with(['category', 'merchant', 'images', 'colorVariants'])
            ->where('status', 'approved')
            ->where('rating', '>=', 4.0)
            ->orderBy('rating', 'desc')
            ->take(12)
            ->get();

        return response()->json($products);
    }

    // Nouveaux arrivages
    public function newArrivals()
    {
        $products = Product::with(['category', 'merchant', 'images', 'colorVariants'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();

        return response()->json($products);
    }

    // Produits tendance (les plus vus/achetés)
    public function trendingProducts()
    {
        $products = Product::with(['category', 'merchant', 'images', 'colorVariants'])
            ->where('status', 'approved')
            ->orderBy('reviews', 'desc')
            ->take(12)
            ->get();

        return response()->json($products);
    }

    // Meilleures ventes
    public function bestSellers()
    {
        $products = Product::with(['category', 'merchant', 'images', 'colorVariants'])
            ->where('status', 'approved')
            ->orderBy('reviews', 'desc')
            ->take(12)
            ->get();

        return response()->json($products);
    }

    // Flash deals / Promotions limitées
    public function flashDeals()
    {
        $products = Product::with(['category', 'merchant', 'images', 'colorVariants'])
            ->where('status', 'approved')
            ->whereColumn('price', '<', 'original_price')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return response()->json($products);
    }

    // Recherche de produits
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        
        $products = Product::with(['category', 'merchant', 'images'])
            ->where('status', 'approved')
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->paginate(20);

        return response()->json($products);
    }

    // Filtrer les produits
    public function filter(Request $request)
    {
        $query = Product::with(['category', 'merchant', 'images'])
            ->where('status', 'approved');

        // Filtre par catégorie
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filtre par sous-catégorie
        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        // Filtre par prix
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filtre par note
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Tri
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20);

        return response()->json($products);
    }

    // Produits par sous-catégorie
    public function bySubCategory($subCategoryId)
    {
        $products = Product::with(['category', 'merchant', 'images'])
            ->where('status', 'approved')
            ->where('sub_category_id', $subCategoryId)
            ->paginate(20);

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
}