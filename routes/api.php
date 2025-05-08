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
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SkillsController;








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
        Route::get('/', [SupplierController::class, 'index']);
        Route::get('/by-user/{userId}', [SupplierController::class, 'getSuppliersByUserId']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::post('/{id}', [SupplierController::class, 'update']);
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
        Route::get('/{id}/products', [SupplierController::class, 'getSupplierProducts']);
        Route::post('/{supplier}/products/import', [ProductController::class, 'import']);

    });

    // Products
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::post('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::get('/{id}', [ProductController::class, 'showWithSupplier'])->whereNumber('id');
        Route::get('/{id}/store', [ProductController::class, 'getStore']);
        Route::get('/{type}', [ProductController::class, 'index'])->where('type', 'workshop|importer|merchant');;
        Route::get('/', [ProductController::class, 'all']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::post('/buy-now', [OrderController::class, 'buyNow']);
        Route::post('/add-to-cart', [OrderController::class, 'addToCart']);
        Route::put('/{orderId}/validate', [OrderController::class, 'validateCart']);
        Route::get('/cart', [OrderController::class, 'getCart']);
        Route::put('/cart/update', [OrderController::class, 'updateCart']);
        Route::delete('/cart/remove/{product_id}', [OrderController::class, 'removeFromCart'])->whereNumber('product_id');
    });

    //service_providers
    Route::prefix('service-providers')->group(function () {//serviceproviders
        Route::post('/', [ServiceProviderController::class, 'store']);
        Route::get('/', [ServiceProviderController::class, 'index']);
        Route::put('/{id}', [ServiceProviderController::class, 'update'])->whereNumber('id');
        Route::post('/{id}/portfolio/pictures', [ServiceProviderController::class, 'uploadPictures']);
        Route::post('/{id}/portfolio/projects', [ProjectController::class, 'store']);
        Route::delete('/{id}', [ServiceProviderController::class, 'destroy']);
        Route::get('/{id}', [ServiceProviderController::class, 'show'])->whereNumber('id');
        Route::get('/by-user/{user_id}', [ServiceProviderController::class, 'showByUser'])->whereNumber('user_id');
        Route::delete('/portfolio/projects/{id}', [ProjectController::class, 'destroy']);
        Route::get('/{id}/portfolio', [ServiceProviderController::class, 'getPortfolio'])->whereNumber('id');
        Route::delete('portfolio/pictures/{id}', [ServiceProviderController::class, 'deletePicture'])->whereNumber('id');
    });



    //service_providers_projects
    




});





// Public Routes
Route::get('/domains', [DomainController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/skills', [SkillsController::class, 'index']);
Route::get('/skill-domains', [SkillsController::class, 'indexDomains']);






