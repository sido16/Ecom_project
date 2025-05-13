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
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\WorkspaceController;










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
    Route::prefix('service-providers')->group(function () {
        Route::post('/', [ServiceProviderController::class, 'store']);
        Route::get('/', [ServiceProviderController::class, 'index']);
        Route::put('/{service_provider_id}', [ServiceProviderController::class, 'update'])->whereNumber('service_provider_id');
        Route::post('/{service_provider_id}/portfolio/pictures', [ServiceProviderController::class, 'uploadPictures'])->whereNumber('service_provider_id');
        Route::post('/{service_provider_id}/portfolio/projects', [ProjectController::class, 'store'])->whereNumber('service_provider_id');
        Route::post('/{service_provider_id}/portfolio/projects/{project_id}', [ProjectController::class, 'update'])->whereNumber(['service_provider_id', 'project_id']);
        Route::delete('/{service_provider_id}', [ServiceProviderController::class, 'destroy'])->whereNumber('service_provider_id');
        Route::get('/{service_provider_id}', [ServiceProviderController::class, 'show'])->whereNumber('service_provider_id');
        Route::get('/by-user/{user_id}', [ServiceProviderController::class, 'showByUser'])->whereNumber('user_id');
        Route::delete('/portfolio/projects/{project_id}', [ProjectController::class, 'destroy'])->whereNumber('project_id');
        Route::get('/{service_provider_id}/portfolio', [ServiceProviderController::class, 'getPortfolio'])->whereNumber('service_provider_id');
        Route::delete('/portfolio/pictures/{picture_id}', [ServiceProviderController::class, 'deletePicture'])->whereNumber('picture_id');
    });


    Route::prefix('service-orders')->group(function () {
        Route::post('/', [ServiceOrderController::class, 'store']);
        Route::patch('{id}/status', [ServiceOrderController::class, 'updateStatus'])->whereNumber('id');
        Route::get('{id}', [ServiceOrderController::class, 'show'])->whereNumber('id');
        Route::get('user/{user_id}', [ServiceOrderController::class, 'getByUser'])->whereNumber('user_id');
        Route::get('service-provider/{service_provider_id}', [ServiceOrderController::class, 'getByServiceProvider'])->whereNumber('service_provider_id');
    });
    //service_providers_projects
    
  

    //sudios 
        Route::post('/workspaces/studio/create', [WorkspaceController::class, 'createStudio']);
        Route::post('/workspaces/coworking/create', [WorkspaceController::class, 'createCoworking']);
        Route::post('/workspaces/{workspace_id}/studio/images', [WorkspaceController::class, 'insertStudioPictures']);
        Route::post('/workspaces/{workspace_id}/coworking/images', [WorkspaceController::class, 'insertCoworkingPictures']);
        Route::get('/workspaces/type/{type}', [WorkspaceController::class, 'getWorkspacesByType']);
        Route::get('/workspaces/{workspace_id}', [WorkspaceController::class, 'getWorkspaceById']);

});






// Public Routes
Route::get('/domains', [DomainController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/skills', [SkillsController::class, 'index']);
Route::get('/skill-domains', [SkillsController::class, 'indexDomains']);






