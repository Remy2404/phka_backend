<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeautyController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'profile']);

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });
});

// Product Routes
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/featured', [ProductController::class, 'featured']);
    Route::get('/search', [ProductController::class, 'search']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/{id}/variants', [ProductController::class, 'variants']);
    Route::get('/{id}/reviews', [ProductController::class, 'reviews']);
    Route::get('/{id}/similar', [ProductController::class, 'similar']);
});

// Category Routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}/products', [ProductController::class, 'byCategory']);

// Shopping Cart Routes (Protected)
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/items', [CartController::class, 'addItem']);
    Route::put('/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/items/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/clear', [CartController::class, 'clearCart']);
    Route::get('/summary', [CartController::class, 'summary']);
    Route::get('/validate', [CartController::class, 'validateCart']);
});

// Order Routes (Protected)
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{orderId}', [OrderController::class, 'show']);
    Route::post('/{orderId}/cancel', [OrderController::class, 'cancel']);
    Route::get('/{orderId}/tracking', [OrderController::class, 'tracking']);
});

// User Profile Routes (Protected)
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/avatar', [UserController::class, 'updateAvatar']);

    // Addresses
    Route::get('/addresses', [UserController::class, 'addresses']);
    Route::post('/addresses', [UserController::class, 'createAddress']);
    Route::put('/addresses/{addressId}', [UserController::class, 'updateAddress']);
    Route::delete('/addresses/{addressId}', [UserController::class, 'deleteAddress']);

    // Wishlist
    Route::get('/wishlist', [UserController::class, 'wishlist']);
    Route::post('/wishlist', [UserController::class, 'addToWishlist']);
    Route::delete('/wishlist/{productId}', [UserController::class, 'removeFromWishlist']);

    // Recently Viewed
    Route::get('/recently-viewed', [UserController::class, 'recentlyViewed']);

    // Statistics
    Route::get('/stats', [UserController::class, 'stats']);
});

// Image Upload Routes (Protected)
Route::middleware('auth:sanctum')->prefix('images')->group(function () {
    Route::post('/products/{productId}', [ImageController::class, 'uploadProductImages']);
    Route::post('/reviews/{reviewId}', [ImageController::class, 'uploadReviewImages']);
    Route::post('/categories/{categoryId}', [ImageController::class, 'uploadCategoryImage']);
    Route::post('/brands/{brandId}', [ImageController::class, 'uploadBrandLogo']);
    Route::post('/stores/{storeId}', [ImageController::class, 'uploadStoreImage']);
    Route::post('/beauty-tips/{tipId}', [ImageController::class, 'uploadBeautyTipImage']);
    Route::post('/tutorials/{videoId}', [ImageController::class, 'uploadTutorialThumbnail']);
    Route::delete('/products/{imageId}', [ImageController::class, 'deleteProductImage']);
    Route::delete('/reviews/{imageId}', [ImageController::class, 'deleteReviewImage']);
});

// Beauty Features Routes
Route::prefix('beauty')->group(function () {
    Route::get('/quizzes', [BeautyController::class, 'quizzes']);
    Route::get('/quizzes/{id}', [BeautyController::class, 'quizShow']);
    Route::get('/tips', [BeautyController::class, 'tips']);
    Route::get('/tips/{id}', [BeautyController::class, 'tipShow']);
    Route::get('/tutorials', [BeautyController::class, 'tutorials']);
    Route::get('/tutorials/{id}', [BeautyController::class, 'tutorialShow']);
});

// Community Routes
Route::prefix('community')->group(function () {
    Route::get('/posts', [CommunityController::class, 'posts']);
    Route::get('/posts/{id}', [CommunityController::class, 'postShow']);
});

// Support Routes
Route::prefix('support')->group(function () {
    Route::get('/faqs', [SupportController::class, 'faqs']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/tickets', [SupportController::class, 'tickets']);
        Route::post('/tickets', [SupportController::class, 'createTicket']);
        Route::get('/tickets/{id}', [SupportController::class, 'ticketShow']);
        Route::post('/tickets/{id}/messages', [SupportController::class, 'addMessage']);
    });
});

// Store Locations
Route::get('/stores', [StoreController::class, 'index']);
Route::get('/stores/{id}', [StoreController::class, 'show']);