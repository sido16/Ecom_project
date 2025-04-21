<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DomainController;



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
    Route::post('suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/by-user/{userId}', [SupplierController::class, 'getSuppliersByUserId']);
    Route::get('suppliers/{id}', [SupplierController::class, 'show']);
    Route::post('suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('suppliers/{id}', [SupplierController::class, 'destroy']);
    Route::post('products', [ProductController::class, 'store']);
    Route::post('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
    Route::get('/domains', [DomainController::class, 'index']);
});