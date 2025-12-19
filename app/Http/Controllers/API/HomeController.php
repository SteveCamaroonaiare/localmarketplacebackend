<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Récupérer toutes les données pour la page d'accueil en une seule requête
     */
    public function index()
    {
        try {
            $data = [
                // Produits vedettes
                'featured_products' => Product::with(['category', 'merchant', 'images'])
                    ->where('status', 'approved')
                    ->where('rating', '>=', 4.0)
                    ->orderBy('rating', 'desc')
                    ->take(8)
                    ->get(),

                // Nouveaux arrivages
                'new_arrivals' => Product::with(['category', 'merchant', 'images'])
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->take(8)
                    ->get(),

                // Flash deals
                'flash_deals' => Product::with(['category', 'merchant', 'images'])
                    ->where('status', 'approved')
                    ->whereColumn('price', '<', 'original_price')
                    ->orderBy('created_at', 'desc')
                    ->take(6)
                    ->get(),

                // Catégories populaires
                'popular_categories' => Category::withCount([
                    'products' => function($query) {
                        $query->where('status', 'approved');
                    }
                ])
                    ->orderBy('products_count', 'desc')
                    ->take(8)
                    ->get(),

                // Statistiques générales
                'stats' => [
                    'total_products' => Product::where('status', 'approved')->count(),
                    'total_categories' => Category::count(),
                    'active_deals' => Product::where('status', 'approved')
                        ->whereColumn('price', '<', 'original_price')
                        ->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la page d\'accueil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les produits par section
     */
    public function getSection($section)
    {
        try {
            $query = Product::with(['category', 'merchant', 'images'])
                ->where('status', 'approved');

            switch ($section) {
                case 'featured':
                    $products = $query->where('rating', '>=', 4.0)
                        ->orderBy('rating', 'desc')
                        ->take(12)
                        ->get();
                    break;

                case 'new-arrivals':
                    $products = $query->orderBy('created_at', 'desc')
                        ->take(12)
                        ->get();
                    break;

                case 'best-sellers':
                    $products = $query->orderBy('reviews', 'desc')
                        ->take(12)
                        ->get();
                    break;

                case 'trending':
                    $products = $query->orderBy('reviews', 'desc')
                        ->orderBy('rating', 'desc')
                        ->take(12)
                        ->get();
                    break;

                case 'deals':
                    $products = $query->whereColumn('price', '<', 'original_price')
                        ->orderBy('created_at', 'desc')
                        ->take(12)
                        ->get();
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Section inconnue'
                    ], 404);
            }

            return response()->json([
                'success' => true,
                'products' => $products,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des produits',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les bannières/sliders pour la page d'accueil
     */
    public function getBanners()
    {
        // Vous pouvez créer une table banners plus tard
        // Pour l'instant, retourner des produits en promotion comme bannières
        
        $banners = Product::with(['category', 'merchant', 'images'])
            ->where('status', 'approved')
            ->whereColumn('price', '<', 'original_price')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($product) {
                $discount = round((($product->original_price - $product->price) / $product->original_price) * 100);
                
                return [
                    'id' => $product->id,
                    'title' => $product->name,
                    'subtitle' => "Économisez {$discount}%",
                    'image' => $product->image ?? $product->images->first()?->image_path,
                    'link' => "/products/{$product->id}",
                    'discount' => $discount,
                ];
            });

        return response()->json([
            'success' => true,
            'banners' => $banners,
        ]);
    }
}