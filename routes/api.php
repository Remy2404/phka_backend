<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

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
Route::prefix('categories')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Models\Category::active()->orderBy('name')->get()
        ]);
    });

    Route::get('/{id}/products', [ProductController::class, 'byCategory']);
});

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

// Beauty Features Routes
Route::prefix('beauty')->group(function () {
    // Beauty Quizzes
    Route::get('/quizzes', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Models\BeautyQuiz::active()->with('questions')->get()
        ]);
    });

    Route::get('/quizzes/{id}', function ($id) {
        $quiz = \App\Models\BeautyQuiz::with('questions')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $quiz
        ]);
    });

    // Beauty Tips
    Route::get('/tips', function (Request $request) {
        $query = \App\Models\BeautyTip::active()->with('creator:id,name');

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        $tips = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $tips
        ]);
    });

    Route::get('/tips/{id}', function ($id) {
        $tip = \App\Models\BeautyTip::with('creator:id,name')->findOrFail($id);
        $tip->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $tip
        ]);
    });

    // Tutorial Videos
    Route::get('/tutorials', function (Request $request) {
        $query = \App\Models\TutorialVideo::active()->with('creator:id,name');

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        $videos = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $videos
        ]);
    });

    Route::get('/tutorials/{id}', function ($id) {
        $video = \App\Models\TutorialVideo::with('creator:id,name')->findOrFail($id);
        $video->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $video
        ]);
    });
});

// Community Routes
Route::prefix('community')->group(function () {
    Route::get('/posts', function (Request $request) {
        $query = \App\Models\CommunityPost::active()->with('user:id,name,avatar');

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $posts = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    });

    Route::get('/posts/{id}', function ($id) {
        $post = \App\Models\CommunityPost::with([
            'user:id,name,avatar',
            'comments' => function($query) {
                $query->with('user:id,name,avatar')->active()->orderBy('created_at');
            }
        ])->findOrFail($id);

        $post->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $post
        ]);
    });
});

// Support Routes
Route::prefix('support')->group(function () {
    // FAQs
    Route::get('/faqs', function (Request $request) {
        $query = \App\Models\FAQ::active();

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $faqs = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $faqs
        ]);
    });

    // Support Tickets (Protected)
    Route::middleware('auth:sanctum')->prefix('tickets')->group(function () {
        Route::get('/', function (Request $request) {
            $tickets = $request->user()->supportTickets()
                ->with('assignedAgent:id,name')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $tickets
            ]);
        });

        Route::post('/', function (Request $request) {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'order_id' => 'nullable|exists:orders,id',
                'product_id' => 'nullable|exists:products,id',
            ]);

            $ticket = \App\Models\SupportTicket::create([
                'user_id' => $request->user()->id,
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'status' => 'open',
                'order_id' => $validated['order_id'] ?? null,
                'product_id' => $validated['product_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Support ticket created successfully',
                'data' => $ticket
            ], 201);
        });

        Route::get('/{id}', function (Request $request, $id) {
            $ticket = $request->user()->supportTickets()
                ->with(['messages.user:id,name', 'assignedAgent:id,name'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $ticket
            ]);
        });

        Route::post('/{id}/messages', function (Request $request, $id) {
            $ticket = $request->user()->supportTickets()->findOrFail($id);

            $validated = $request->validate([
                'message' => 'required|string',
            ]);

            $message = \App\Models\SupportMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
                'is_internal' => false,
            ]);

            return response()->json([
                'success' => true,
                'data' => $message->load('user:id,name')
            ], 201);
        });
    });
});

// Store Locations
Route::get('/stores', function (Request $request) {
    $query = \App\Models\Store::active();

    if ($request->has('search')) {
        $query->search($request->search);
    }

    if ($request->has(['latitude', 'longitude'])) {
        $query->withinRadius($request->latitude, $request->longitude);
    }

    $stores = $query->get();

    return response()->json([
        'success' => true,
        'data' => $stores
    ]);
});

Route::get('/stores/{id}', function ($id) {
    $store = \App\Models\Store::with(['products' => function($query) {
        $query->active()->inStock()->limit(10);
    }])->findOrFail($id);

    return response()->json([
        'success' => true,
        'data' => $store
    ]);
});