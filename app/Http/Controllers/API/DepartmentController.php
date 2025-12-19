<?php
// app/Http/Controllers/API/DepartmentController.php - VERSION COMPLÈTE

namespace App\Http\Controllers\API;

use App\Models\Department;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class DepartmentController extends Controller
{
    /**
     * Liste tous les départements actifs
     */
   
    public function index()
    {
        try {
            $departments = Department::where('active', true)
                                    ->orderBy('order')
                                    ->get();

            return response()->json($departments);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching departments: ' . $e->getMessage());
            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified department.
     */
    public function show($slug)
    {
        try {
            $department = Department::where('slug', $slug)
                                    ->with(['categories' => function($query) {
                                        $query->orderBy('department_category.order');
                                    }])
                                    ->first();

            if (!$department) {
                return response()->json(['message' => 'Department not found'], 404);
            }

            return response()->json($department);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching department: ' . $e->getMessage());
            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products for a department.
     */
    /**
 * Get products for a department.
 */
public function products($slug)
{
    try {
        $department = Department::where('slug', $slug)->first();

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        // MODIFIEZ CETTE PARTIE - Rechercher directement par department_id
        $products = Product::with(['category', 'subCategory', 'colorVariants', 'images'])
                          ->where('department_id', $department->id) // Changé ici
                          ->orderBy('created_at', 'desc')
                          ->paginate(20);

        return response()->json([
            'department' => $department,
            'products' => $products
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error fetching department products: ' . $e->getMessage());
        return response()->json([
            'message' => 'Internal server error',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Tous les départements avec leurs catégories
     */
    public function categoriesWithDepartments()
    {
        try {
            $departments = Department::with([
                'categories' => function($query) {
                    $query->withCount([
                        'products' => function($q) {
                            $q->where('status', 'approved');
                        }
                    ]);
                }
            ])
            ->active()
            ->ordered()
            ->get();

            return response()->json($departments);

        } catch (\Exception $e) {
            Log::error('Erreur categories with departments:', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Erreur chargement',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formater un produit pour l'API
     */
    private function formatProduct($product)
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'original_price' => $product->original_price,
            'rating' => $product->rating ?? 0,
            'reviews' => $product->reviews ?? 0,
            'seller' => $product->merchant->shop_name ?? $product->merchant->name ?? 'Vendeur',
            'location' => $product->merchant->country ?? 'Maroc',
            'badge' => $this->getProductBadge($product),
            'image' => $this->getProductImage($product),
        ];
    }

    /**
     * Obtenir l'image principale du produit
     */
    private function getProductImage($product)
    {
        if ($product->image) {
            return url('storage/' . $product->image);
        }
        
        if ($product->images && $product->images->count() > 0) {
            $primaryImage = $product->images->where('is_primary', true)->first() 
                         ?? $product->images->first();
            return url('storage/' . $primaryImage->image_path);
        }
        
        return '/placeholder.svg';
    }

    /**
     * Obtenir le badge du produit
     */
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
}