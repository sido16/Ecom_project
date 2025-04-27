<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategoryController;




/*
|--------------------------------------------------------------------------
| Public Authentication Routes
|--------------------------------------------------------------------------
|
| Routes for registration, login, and email verification, accessible without authentication.
|
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
|
| Routes requiring Sanctum authentication, grouped under the 'auth:sanctum' middleware.
|
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']); 
    Route::post('/user/update', [AuthController::class, 'update']);
    Route::put('/user/update-password', [AuthController::class, 'updatePassword']);
});

// routes/api.php



Route::middleware('auth:sanctum')->group(function () {
    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::post('/', [SupplierController::class, 'store']);
        Route::get('/by-user/{userId}', [SupplierController::class, 'getSuppliersByUserId']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::post('/{id}', [SupplierController::class, 'update']);
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
        Route::get('/{id}/products', [SupplierController::class, 'getSupplierProducts']);
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::post('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::get('/{id}', [ProductController::class, 'show']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::post('/buy-now', [OrderController::class, 'buyNow']);
        Route::post('/add-to-cart', [OrderController::class, 'addToCart']);
        Route::put('/{orderId}/validate', [OrderController::class, 'validateCart']);
    });
});

// Public Routes
Route::get('/domains', [DomainController::class, 'index']);
Route::get('/Categories', [CategoryController::class, 'index']);





