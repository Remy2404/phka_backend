<?php

use App\Http\Controllers\Api\AdminController;
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
| CONSOLIDATED ROUTE STRUCTURE:
| - All business entity management (CRUD) is in /admin/* routes 
| - Public routes only for viewing/browsing
| - User routes only for personal data/interactions
|
| SECURITY LEVELS:
| - Public: Viewing products, categories, content (GET only)
| - User: Personal data, cart, orders, own content (auth:sanctum)
| - Admin: All business management (auth:sanctum + admin)
| - Super Admin: Role management (auth:sanctum + admin + super_admin)
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

// Public Content Viewing (Read-only)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/{id}/variants', [ProductController::class, 'variants']);
Route::get('/products/{id}/reviews', [ProductController::class, 'reviews']);
Route::get('/products/{id}/similar', [ProductController::class, 'similar']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}/products', [ProductController::class, 'byCategory']);

Route::get('/beauty/quizzes', [BeautyController::class, 'quizzes']);
Route::get('/beauty/quizzes/{id}', [BeautyController::class, 'quizShow']);
Route::get('/beauty/tips', [BeautyController::class, 'tips']);
Route::get('/beauty/tips/{id}', [BeautyController::class, 'tipShow']);
Route::get('/beauty/tutorials', [BeautyController::class, 'tutorials']);
Route::get('/beauty/tutorials/{id}', [BeautyController::class, 'tutorialShow']);

Route::get('/community/posts', [CommunityController::class, 'posts']);
Route::get('/community/posts/{id}', [CommunityController::class, 'postShow']);

Route::get('/support/faqs', [SupportController::class, 'faqs']);

Route::get('/stores', [StoreController::class, 'index']);
Route::get('/stores/{id}', [StoreController::class, 'show']);

// User Personal Data & Interactions (Protected)
Route::middleware('auth:sanctum')->group(function () {
    // Shopping Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('/items/{itemId}', [CartController::class, 'removeItem']);
        Route::delete('/clear', [CartController::class, 'clearCart']);
        Route::get('/summary', [CartController::class, 'summary']);
        Route::get('/validate', [CartController::class, 'validateCart']);
    });

    // User Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{orderId}', [OrderController::class, 'show']);
        Route::post('/{orderId}/cancel', [OrderController::class, 'cancel']);
        Route::get('/{orderId}/tracking', [OrderController::class, 'tracking']);
    });

    // User Profile
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
        Route::get('/addresses', [UserController::class, 'addresses']);
        Route::post('/addresses', [UserController::class, 'createAddress']);
        Route::put('/addresses/{addressId}', [UserController::class, 'updateAddress']);
        Route::delete('/addresses/{addressId}', [UserController::class, 'deleteAddress']);
        Route::get('/wishlist', [UserController::class, 'wishlist']);
        Route::post('/wishlist', [UserController::class, 'addToWishlist']);
        Route::delete('/wishlist/{productId}', [UserController::class, 'removeFromWishlist']);
        Route::get('/recently-viewed', [UserController::class, 'recentlyViewed']);
        Route::get('/stats', [UserController::class, 'stats']);
    });

    // User Interactions
    Route::post('/beauty/quizzes/{id}/take', [BeautyController::class, 'takeQuiz']);
    Route::get('/beauty/quiz-results', [BeautyController::class, 'myQuizResults']);
    
    Route::post('/community/posts', [CommunityController::class, 'createPost']);
    Route::put('/community/posts/{id}', [CommunityController::class, 'updatePost']); // Own posts only
    Route::delete('/community/posts/{id}', [CommunityController::class, 'deletePost']); // Own posts only
    Route::post('/community/posts/{id}/like', [CommunityController::class, 'likePost']);
    Route::post('/community/posts/{id}/comments', [CommunityController::class, 'addComment']);
    Route::get('/community/my-posts', [CommunityController::class, 'myPosts']);
    
    Route::get('/support/tickets', [SupportController::class, 'tickets']); // Own tickets only
    Route::post('/support/tickets', [SupportController::class, 'createTicket']);
    Route::get('/support/tickets/{id}', [SupportController::class, 'ticketShow']); // Own tickets only
    Route::post('/support/tickets/{id}/messages', [SupportController::class, 'addMessage']);
    
    // User Review Images Only
    Route::post('/images/reviews/{reviewId}', [ImageController::class, 'uploadReviewImages']);
    Route::delete('/images/reviews/{imageId}', [ImageController::class, 'deleteReviewImage']);
});

// ===========================================
// ADMIN ROUTES - Single source for ALL business management
// ===========================================
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Dashboard and Analytics
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/analytics/overview', [AdminController::class, 'analyticsOverview']);
    Route::get('/analytics/sales', [AdminController::class, 'salesAnalytics']);
    Route::get('/analytics/users', [AdminController::class, 'userAnalytics']);
    Route::get('/reports/low-stock', [AdminController::class, 'lowStockReport']);
    
    // User Management
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users', [AdminController::class, 'createAdminUser']);
    
    // Product Management (Complete CRUD)
    Route::get('/products', [AdminController::class, 'getProducts']);
    Route::post('/products', [AdminController::class, 'createProduct']);
    Route::put('/products/{productId}', [AdminController::class, 'updateProduct']);
    Route::delete('/products/{productId}', [AdminController::class, 'deleteProduct']);
    Route::get('/products/{productId}/reviews', [AdminController::class, 'productReviews']);
    Route::put('/reviews/{reviewId}/moderate', [AdminController::class, 'moderateReview']);
    
    // Category Management (Complete CRUD)
    Route::get('/categories', [AdminController::class, 'getCategories']);
    Route::post('/categories', [AdminController::class, 'createCategory']);
    Route::put('/categories/{categoryId}', [AdminController::class, 'updateCategory']);
    Route::delete('/categories/{categoryId}', [AdminController::class, 'deleteCategory']);
    
    // Beauty Content Management (Complete CRUD)
    Route::get('/beauty/tips', [AdminController::class, 'getBeautyTips']);
    Route::post('/beauty/tips', [AdminController::class, 'createBeautyTip']);
    Route::put('/beauty/tips/{tipId}', [AdminController::class, 'updateBeautyTip']);
    Route::delete('/beauty/tips/{tipId}', [AdminController::class, 'deleteBeautyTip']);
    Route::get('/beauty/videos', [AdminController::class, 'getTutorialVideos']);
    Route::post('/beauty/videos', [AdminController::class, 'createTutorialVideo']);
    Route::put('/beauty/videos/{videoId}', [AdminController::class, 'updateTutorialVideo']);
    Route::delete('/beauty/videos/{videoId}', [AdminController::class, 'deleteTutorialVideo']);
    Route::get('/beauty/quizzes', [AdminController::class, 'getAllQuizzes']);
    
    // Community Moderation (Complete management)
    Route::get('/community/posts', [AdminController::class, 'getCommunityPosts']);
    Route::put('/community/posts/{postId}/moderate', [AdminController::class, 'moderateCommunityPost']);
    
    // Support Management (Complete CRUD)
    Route::get('/support/faqs', [AdminController::class, 'getFAQs']);
    Route::post('/support/faqs', [AdminController::class, 'createFAQ']);
    Route::put('/support/faqs/{faqId}', [AdminController::class, 'updateFAQ']);
    Route::delete('/support/faqs/{faqId}', [AdminController::class, 'deleteFAQ']);
    Route::get('/support/tickets', [AdminController::class, 'getSupportTickets']);
    Route::put('/support/tickets/{ticketId}', [AdminController::class, 'updateSupportTicket']);
    
    // Store Management (Complete CRUD)
    Route::get('/stores', [AdminController::class, 'getStores']);
    Route::post('/stores', [AdminController::class, 'createStore']);
    Route::put('/stores/{storeId}', [AdminController::class, 'updateStore']);
    Route::delete('/stores/{storeId}', [AdminController::class, 'deleteStore']);
    
    // Order Management (Complete management)
    Route::get('/orders', [AdminController::class, 'orders']);
    Route::put('/orders/{orderId}/status', [AdminController::class, 'updateOrderStatus']);
    
    // Image Management (All business content)
    Route::post('/images/products/{productId}', [ImageController::class, 'uploadProductImages']);
    Route::post('/images/categories/{categoryId}', [ImageController::class, 'uploadCategoryImage']);
    Route::post('/images/brands/{brandId}', [ImageController::class, 'uploadBrandLogo']);
    Route::post('/images/stores/{storeId}', [ImageController::class, 'uploadStoreImage']);
    Route::post('/images/beauty-tips/{tipId}', [ImageController::class, 'uploadBeautyTipImage']);
    Route::post('/images/tutorials/{videoId}', [ImageController::class, 'uploadTutorialThumbnail']);
    Route::delete('/images/products/{imageId}', [ImageController::class, 'deleteProductImage']);
    
    // Super Admin Only Routes
    Route::middleware('super_admin')->group(function () {
        Route::put('/users/{userId}/role', [AdminController::class, 'updateUserRole']);
    });
});