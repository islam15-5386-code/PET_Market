<?php

use App\Http\Controllers\Api\Admin\CategoryManagementController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\OrderManagementController;
use App\Http\Controllers\Api\Admin\ProductManagementController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Order\OrderController;
use App\Http\Controllers\Api\Product\CategoryController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\User\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Pet Marketplace  (All Phases Complete)
|--------------------------------------------------------------------------
*/

// ── Health Check ──────────────────────────────────────────────────────────────
Route::get('health', fn () => response()->json([
    'status'  => 'ok',
    'service' => 'Pet Marketplace API',
    'version' => '1.0.0',
]));

// ── Public Auth ───────────────────────────────────────────────────────────────
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register',        [AuthController::class, 'register'])->name('register');
    Route::post('login',           [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('reset-password',  [AuthController::class, 'resetPassword'])->name('reset-password');
});

// ── Social OAuth (Google / Facebook) ─────────────────────────────────────────
Route::prefix('auth/social')->name('auth.social.')->group(function () {
    Route::get('{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('redirect');
    Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])->name('callback');
});

// ── Public Product Browsing ───────────────────────────────────────────────────
Route::get('categories',      [CategoryController::class, 'index'])->name('categories.index');
Route::get('products',        [ProductController::class, 'index'])->name('products.index');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('ai/product-search', [AIController::class, 'productSearch'])->name('ai.product-search');

// ── Authenticated Routes ──────────────────────────────────────────────────────
Route::middleware('auth.jwt')->group(function () {

    // Auth management
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout'])->name('logout');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::get('me',       [AuthController::class, 'me'])->name('me');
    });

    // ── User Profile ──────────────────────────────────────────────────────────
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('profile',         [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile',         [ProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
        Route::get('orders',          [ProfileController::class, 'orders'])->name('orders.index');
    });

    // ── Cart ──────────────────────────────────────────────────────────────────
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/',               [CartController::class, 'index'])->name('index');
        Route::post('/',              [CartController::class, 'add'])->name('add');
        Route::put('/{productId}',    [CartController::class, 'update'])->name('update');
        Route::delete('/{productId}', [CartController::class, 'remove'])->name('remove');
        Route::delete('/',            [CartController::class, 'clear'])->name('clear');
    });

    // ── Orders ────────────────────────────────────────────────────────────────
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::post('preview',               [OrderController::class, 'preview'])->name('preview');
        Route::post('/',                     [OrderController::class, 'store'])->name('store');
        Route::get('/{orderNumber}',         [OrderController::class, 'show'])->name('show');
        Route::post('/{orderNumber}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    });

    // ── Admin (requires admin role) ───────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {

        // Dashboard
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/',        [DashboardController::class, 'index'])->name('index');
            Route::get('/revenue', [DashboardController::class, 'revenue'])->name('revenue');
        });

        // Product management
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/',                       [ProductManagementController::class, 'index'])->name('index');
            Route::post('/',                      [ProductManagementController::class, 'store'])->name('store');
            Route::get('/{id}',                   [ProductManagementController::class, 'show'])->name('show');
            Route::put('/{id}',                   [ProductManagementController::class, 'update'])->name('update');
            Route::delete('/{id}',                [ProductManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/generate-description', [ProductManagementController::class, 'generateDescription'])->name('generate-description');
            Route::post('/{id}/images',           [ProductManagementController::class, 'uploadImages'])->name('images.upload');
            Route::delete('/{id}/images/{index}', [ProductManagementController::class, 'deleteImage'])->name('images.delete');
        });

        // Category management
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::post('/',      [CategoryManagementController::class, 'store'])->name('store');
            Route::put('/{id}',   [CategoryManagementController::class, 'update'])->name('update');
            Route::delete('/{id}',[CategoryManagementController::class, 'destroy'])->name('destroy');
        });

        // User management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',              [UserManagementController::class, 'index'])->name('index');
            Route::get('/{id}',          [UserManagementController::class, 'show'])->name('show');
            Route::post('/{id}/block',   [UserManagementController::class, 'block'])->name('block');
            Route::post('/{id}/unblock', [UserManagementController::class, 'unblock'])->name('unblock');
            Route::get('/{id}/orders',   [UserManagementController::class, 'orders'])->name('orders');
        });

        // Order management
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/',                          [OrderManagementController::class, 'index'])->name('index');
            Route::get('/{orderNumber}',             [OrderManagementController::class, 'show'])->name('show');
            Route::put('/{orderNumber}/status',      [OrderManagementController::class, 'updateStatus'])->name('status');
        });

    });

});
