<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController; 
use App\Http\Controllers\API\DepartmentController; 
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

Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store'])->middleware('auth:sanctum');

Route::get('/products/{id}/similar', [ProductController::class, 'similar']);Route::get('/test', function() {
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