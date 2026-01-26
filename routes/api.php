<?php
use App\Http\Controllers\API\AuthController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController; 
use App\Http\Controllers\API\DepartmentController; 

use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\MerchantAuthController;
use App\Http\Controllers\API\MerchantDashboardController;
use App\Http\Controllers\API\OrderController;
 use App\Http\Controllers\API\SubscriptionController;   
 use App\Http\Controllers\API\FinanceController;    
 use App\Http\Controllers\API\MerchantProductController;  
 use App\Http\Controllers\API\AdminProductController;   
  use App\Http\Controllers\API\AdminManagementController;
  use App\Http\Controllers\API\AdminMerchantController;
  use App\Http\Controllers\API\SuperAdminController;
  use App\Http\Controllers\API\GoogleMerchantController;    
  use App\Http\Controllers\API\GoogleAuthController;
  use App\Http\Controllers\API\HomeController;
  use App\Http\Controllers\API\ConversationController;
  use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\MerchantFollowController;
use App\Http\Controllers\API\MerchantPublicController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});




// ========================================
// ROUTES PUBLIQUES - Pour la page d'accueil et l'affichage général
// ========================================

// Page d'accueil - Produits vedettes / nouveautés
Route::get('/homepage/featured-products', [ProductController::class, 'featuredProducts']);
Route::get('/homepage/new-arrivals', [ProductController::class, 'newArrivals']);
Route::get('/homepage/trending', [ProductController::class, 'trendingProducts']);
Route::get('/homepage/best-sellers', [ProductController::class, 'bestSellers']);
Route::get('/homepage/flash-deals', [ProductController::class, 'flashDeals']);

// Produits par catégorie (déjà existant mais assurez-vous qu'il filtre approved)
Route::get('/categories/{id}/products', [ProductController::class, 'byCategory']);

// Produits par sous-catégorie
Route::get('/subcategories/{id}/products', [ProductController::class, 'bySubCategory']);

// Tous les produits (avec pagination, recherche, filtres)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Recherche et filtres
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/filter', [ProductController::class, 'filter']);

// Promotions
Route::get('/promotions', [ProductController::class, 'promotionalProducts']);



// Route temporaire pour tester
Route::get('/products-with-merchant', function() {
    $products = \App\Models\Product::with('merchant')
        ->where('status', 'approved')
        ->get()
        ->map(function($product) {
            return [
                'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'originalPrice' => $product->original_price,
                        'image' => $product->image,
                        'badge' => $product->badge,
                        'rating' => $product->rating,
                        'reviews' => $product->reviews,
                        'location' => $product->location,
                        // 🔥 Assurez-vous d'ajouter merchant_id et seller
                        'merchant_id' => $product->merchant_id,
                        'seller' => $product->merchant ? ($product->merchant->shop_name ?? $product->merchant->name) : null,
                        'department_slug' => optional($product->department)->slug,
                   
            ];
        });
    
    return response()->json($products);
});







Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/recommended', [CategoryController::class, 'recommended']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/categories/{id}/products', [CategoryController::class, 'byCategory']);

// Nouvelles routes pour les sous-catégories
Route::get('/subcategories', [CategoryController::class, 'subCategoriesIndex']);
Route::get('/subcategories/{id}', [CategoryController::class, 'subCategoryShow']);
Route::get('/subcategories/{id}/products', [CategoryController::class, 'bySubCategory']);

Route::get('/categories/{id}/products', [ProductController::class, 'byCategory']);
Route::put('/products/{id}/stock', [ProductController::class, 'updateStock']);
Route::put('products/restore-stock', [ProductController::class, 'restoreStock']);

Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store'])->middleware('auth:sanctum');

// Route pour les produits similaires (même département)
Route::get('/products/{product}/similar', function ($productId) {
    try {
        $product = Product::find($productId);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        
        // Récupérer les produits du même département (sauf le produit actuel)
        $similarProducts = Product::where('department_id', $product->department_id)
            ->where('id', '!=', $productId)
            ->where('status', 'approved')
            ->limit(12)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'original_price' => $item->original_price,
                    'image' => $item->image,
                    'badge' => $item->badge,
                    'rating' => $item->rating,
                    'reviews' => $item->reviews,
                    'seller' => $item->seller,
                    'location' => $item->location,
                    'department_slug' => $item->department ? $item->department->slug : null,
                ];
            });
        
        return response()->json($similarProducts);
        
    } catch (\Exception $e) {
        \Log::error('Error fetching similar products: ' . $e->getMessage());
        return response()->json([
            'message' => 'Internal server error',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/test', function() {
    return response()->json([
        'message' => 'API fonctionne',
        'timestamp' => now()
    ]);
});
Route::get('/products/department/{slug}', [ProductController::class, 'byDepartment']);

// Routes pour les produits


// Route pour déboguer - version simplifiée
Route::get('/products-simple', function () {
    $products = \App\Models\Product::select('id', 'name', 'price', 'department_id', 'image')
        ->get();
    
    return response()->json([
        'total' => $products->count(),
        'products' => $products
    ]);
});

// Route de test
Route::get('/test-api', function () {
    return response()->json([
        'message' => 'API is working',
        'timestamp' => now(),
        'product_count' => \App\Models\Product::count(),
        'department_count' => \App\Models\Department::count(),
    ]);
});

// Route pour tous les produits avec leurs départements
Route::get('/all-products', function () {
    $products = Product::with('department')
        ->where('status', 'approved')
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'originalPrice' => $product->original_price,
                'image' => $product->image,
                'badge' => $product->badge,
                'rating' => $product->rating,
                'reviews' => $product->reviews,
                'seller' => $product->seller,
                'location' => $product->location,
                'department_slug' => $product->department ? $product->department->slug : null,
            ];
        });
    
    return response()->json($products);
});

// Route pour les produits par département
Route::get('/products-by-department/{slug}', function ($slug) {
    if ($slug === 'all') {
        $products = Product::with('department')
            ->where('status', 'approved')
            ->get();
    } else {
        $department = \App\Models\Department::where('slug', $slug)->first();
        
        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }
        
        $products = Product::with('department')
            ->where('department_id', $department->id)
            ->where('status', 'approved')
            ->get();
    }
    
    return response()->json($products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'originalPrice' => $product->original_price,
            'image' => $product->image,
            'badge' => $product->badge,
            'rating' => $product->rating,
            'reviews' => $product->reviews,
            'seller' => $product->seller,
            'location' => $product->location,
            'department_slug' => $product->department ? $product->department->slug : null,
        ];
    }));
});
// Dans api.php
Route::get('/departments', function () {
    return Department::where('active', true)
        ->orderBy('order')
        ->get(['id', 'name', 'slug', 'order']);
});

// routes/api.php
Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/departments/{slug}', [DepartmentController::class, 'show']);
Route::get('/departments/{slug}/products', [DepartmentController::class, 'products']);
Route::get('/departments-all/categories', [DepartmentController::class, 'categoriesWithDepartments']);




Route::get('/products/promotions', [ProductController::class, 'promotions']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

 Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);


Route::prefix('merchant')->group(function () {
    Route::post('/register', [MerchantAuthController::class, 'register']);
    Route::post('/login', [MerchantAuthController::class, 'login']);
    
    // Routes protégées (nécessitent authentification)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [MerchantDashboardController::class, 'getDashboardData']);
        Route::post('/update-profile', [MerchantAuthController::class, 'updateProfile']);
        Route::put('/update-profile', [MerchantAuthController::class, 'updateProfile']); // Support PUT aussi
        Route::post('/logout', [MerchantAuthController::class, 'logout']);
        Route::get('/profile', [MerchantAuthController::class, 'profile']);
    });
});




Route::get('/auth/google/merchant', [GoogleMerchantController::class, 'redirectToGoogle']);
Route::get('/auth/google/merchant/callback', [GoogleMerchantController::class, 'handleGoogleCallback']);



Route::middleware('auth:sanctum')->group(function () {
    Route::put('/update-profile', [MerchantAuthController::class, 'updateProfile']); // ✅
        Route::post('/merchant/profile', [MerchantAuthController::class, 'updateProfile']);
    Route::post('/merchant/update-profile', [MerchantAuthController::class, 'updateProfile']);

});
// Routes pour les commandes
Route::middleware('auth:sanctum')->group(function () {
    
    // Routes Client (acheter)
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']); // Mes commandes
        Route::get('/{id}', [OrderController::class, 'show']); // Détails commande
        Route::post('/', [OrderController::class, 'store']); // Créer commande
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']); // Annuler
    });

    // Routes Merchant (vendre)
    Route::prefix('merchant')->group(function () {
        Route::get('/orders', [OrderController::class, 'merchantOrders']); // Toutes les commandes du vendeur
        Route::get('/orders/stats', [OrderController::class, 'stats']); // Statistiques
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']); // Changer statut
    });
});
Route::middleware('auth:sanctum')->group(function () {
    
    // Routes finances
    Route::prefix('merchant/finances')->group(function () {
        Route::get('/stats', [FinanceController::class, 'getFinancialStats']);
        Route::get('/monthly-revenue', [FinanceController::class, 'getMonthlyRevenue']);
        Route::get('/transactions', [FinanceController::class, 'getTransactions']);
        Route::get('/payouts', [FinanceController::class, 'getPayoutHistory']); // Nouvelle route
    });

    // Routes d'abonnement
    Route::prefix('merchant/subscription')->group(function () {
        Route::get('/plans', [SubscriptionController::class, 'getPlans']);
        Route::get('/current', [SubscriptionController::class, 'getCurrentSubscription']);
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
        Route::post('/cancel', [SubscriptionController::class, 'cancelSubscription']);
        Route::get('/payment-history', [SubscriptionController::class, 'getPaymentHistory']);
    });
});
Route::middleware('auth:sanctum')->prefix('merchant')->group(function () {
    
    // Produits du merchant
    Route::prefix('products')->group(function () {
        Route::get('/', [MerchantProductController::class, 'index']); // Liste
        Route::post('/', [MerchantProductController::class, 'store']); // Créer
        Route::get('/stats', [MerchantProductController::class, 'stats']); // Statistiques
        Route::get('/{id}', [MerchantProductController::class, 'show']); // Détails
        Route::put('/{id}', [MerchantProductController::class, 'update']); // Modifier
        Route::delete('/{id}', [MerchantProductController::class, 'destroy']); // Supprimer
    });
});
// Routes Super Admin
Route::middleware(['auth:sanctum', 'super_admin'])->prefix('super-admin')->group(function () {
    
    // Gestion des administrateurs
    Route::prefix('admins')->group(function () {
        Route::get('/', [AdminManagementController::class, 'index']);
        Route::post('/', [AdminManagementController::class, 'store']);
        Route::post('/promote/{userId}', [AdminManagementController::class, 'promoteUser']);
        Route::post('/{adminId}/deactivate', [AdminManagementController::class, 'deactivateAdmin']);
        Route::post('/{adminId}/activate', [AdminManagementController::class, 'activateAdmin']);
        Route::get('/stats', [AdminManagementController::class, 'stats']);
    });

    // Dashboard et statistiques
    Route::get('/dashboard-stats', [SuperAdminController::class, 'dashboardStats']);
    Route::get('/admin-activity', [SuperAdminController::class, 'adminActivity']);
    Route::get('/system-audit', [SuperAdminController::class, 'systemAudit']);
});
// Routes pour tous les admins
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    
    // Gestion des produits
    Route::prefix('products')->group(function () {
        Route::get('/pending', [AdminProductController::class, 'pendingProducts']);
        Route::get('/all', [AdminProductController::class, 'allProducts']);
        Route::get('/stats', [AdminProductController::class, 'stats']);
        Route::get('/{id}', [AdminProductController::class, 'show']);
        Route::post('/{id}/approve', [AdminProductController::class, 'approveProduct']);
        Route::post('/{id}/reject', [AdminProductController::class, 'rejectProduct']);
        Route::get('/approval-history', [AdminProductController::class, 'approvalHistory']);
    });

    // Gestion des merchants
    Route::prefix('merchants')->group(function () {
        Route::get('/pending', [AdminMerchantController::class, 'pendingMerchants']);
        Route::get('/stats', [AdminMerchantController::class, 'stats']);
        Route::post('/{id}/approve', [AdminMerchantController::class, 'approveMerchant']);
        Route::post('/{id}/reject', [AdminMerchantController::class, 'rejectMerchant']);
        Route::post('/{id}/deactivate', [AdminMerchantController::class, 'deactivateMerchant']);
    });
});
Route::get('/merchants/{merchant}', [MerchantPublicController::class, 'show']);


// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/merchants/{merchantId}/follow', [MerchantFollowController::class, 'toggleFollow']);
    Route::get('/merchants/{merchantId}/follow-status', [MerchantFollowController::class, 'checkFollowStatus']);
});


// Routes publiques pour la page d'accueil
Route::get('/home', [HomeController::class, 'index']);
Route::get('/home/section/{section}', [HomeController::class, 'getSection']);
Route::get('/home/banners', [HomeController::class, 'getBanners']);

// Routes produits (mettre à jour ProductController avec les méthodes)
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/filter', [ProductController::class, 'filter']);

Route::get('/debug-products', function () {
    $products = \App\Models\Product::all();
    
    if ($products->isEmpty()) {
        return response()->json([
            'message' => 'Aucun produit dans la base de données',
            'total' => 0
        ]);
    }
    
    $formatted = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'department_id' => $product->department_id,
            'department' => $product->department ? [
                'id' => $product->department->id,
                'name' => $product->department->name,
                'slug' => $product->department->slug
            ] : null,
            'has_department' => !is_null($product->department_id),
        ];
    });
    
    return response()->json([
        'total' => $products->count(),
        'products_with_department' => $products->whereNotNull('department_id')->count(),
        'products' => $formatted
    ]);

});


Route::middleware(['auth:sanctum'])->group(function () {
    
    // Conversations
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::post('/conversations/{conversation}/mark-read', [ConversationController::class, 'markAsRead']);
    
    // Messages
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'sendMessage']);
    
    // Statistiques
    Route::get('/conversations/unread-count', function() {
        $user = auth()->user();
        
        $count = Conversation::where(function($query) use ($user) {
                $query->where('customer_id', $user->id)
                      ->orWhereHas('merchant', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->withCount(['messages as unread_count' => function($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                      ->where('is_read', false);
            }])
            ->get()
            ->sum('unread_count');
        
        return response()->json(['count' => $count]);
    });
});
Route::get('/products-test', function() {
    $products = \App\Models\Product::with('merchant')
        ->where('status', 'approved')
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'merchant_id' => $product->merchant_id,
                'seller' => $product->merchant ? $product->merchant->shop_name : null,
            ];
        });
    
    return response()->json($products);
});