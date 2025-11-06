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



Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/recommended', [CategoryController::class, 'recommended']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/categories/{id}/products', [CategoryController::class, 'byCategory']);

// Nouvelles routes pour les sous-catégories
Route::get('/subcategories', [CategoryController::class, 'subCategoriesIndex']);
Route::get('/subcategories/{id}', [CategoryController::class, 'subCategoryShow']);
Route::get('/subcategories/{id}/products', [CategoryController::class, 'bySubCategory']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories/{id}/products', [ProductController::class, 'byCategory']);
Route::put('/products/{id}/stock', [ProductController::class, 'updateStock']);
Route::put('products/restore-stock', [ProductController::class, 'restoreStock']);

Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store'])->middleware('auth:sanctum');

Route::get('/products/{id}/similar', [ProductController::class, 'similar']);
Route::get('/test', function() {
    return response()->json([
        'message' => 'API fonctionne',
        'timestamp' => now()
    ]);
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



use App\Http\Controllers\API\GoogleMerchantController;

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

