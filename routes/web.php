<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\GoogleAuthController;
use App\Http\Controllers\API\GoogleMerchantController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



// Routes Google OAuth - DOIVENT être dans web.php
Route::prefix('auth')->group(function () {
    // Client Google
    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    
    // Merchant Google
    Route::get('google/merchant', [GoogleMerchantController::class, 'redirectToGoogle'])->name('auth.google.merchant');
    Route::get('google/merchant/callback', [GoogleMerchantController::class, 'handleGoogleCallback'])->name('auth.google.merchant.callback');
});
