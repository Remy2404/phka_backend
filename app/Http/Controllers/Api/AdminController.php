<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductReview;
use App\Models\Category;
use App\Models\BeautyTip;
use App\Models\TutorialVideo;
use App\Models\QuizQuestion;
use App\Models\CommunityPost;
use App\Models\PostComment;
use App\Models\FAQ;
use App\Models\SupportTicket;
use App\Models\Store;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin-only endpoints for managing the platform"
 * )
 */
class AdminController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     tags={"Admin"},
     *     summary="Get admin dashboard data",
     *     description="Returns dashboard statistics and metrics for admin users",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_users", type="integer", example=1250),
     *                 @OA\Property(property="total_orders", type="integer", example=890),
     *                 @OA\Property(property="total_products", type="integer", example=450),
     *                 @OA\Property(property="total_revenue", type="number", format="float", example=125000.50)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Admin access required")
     * )
     */
    public function dashboard()
    {
        $data = [
            'total_users' => User::count(),
            'total_orders' => Order::count(),
            'total_products' => Product::count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'new_orders_today' => Order::whereDate('created_at', today())->count(),
            'active_users' => User::where('is_active', true)->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     tags={"Admin"},
     *     summary="Get all users",
     *     description="Returns paginated list of all users for admin management",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         required=false,
     *         description="Filter by role",
     *         @OA\Schema(type="string", enum={"customer","admin","super_admin"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of users",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Admin access required")
     * )
     */
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{userId}/role",
     *     tags={"Admin"},
     *     summary="Update user role",
     *     description="Update a user's role (Super admin only)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", enum={"customer","admin","super_admin"}, example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User role updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Super admin access required"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function updateUserRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|in:customer,admin,super_admin'
        ]);

        $user = User::findOrFail($userId);
        
        // Prevent demoting yourself if you're a super admin
        if ($user->id === $request->user()->id && $request->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own role'
            ], 400);
        }

        $user->update(['role' => $request->role]);

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users",
     *     tags={"Admin"},
     *     summary="Create admin user",
     *     description="Create a new admin user",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","role"},
     *             @OA\Property(property="name", type="string", example="Admin User"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="role", type="string", enum={"admin","super_admin"}, example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Admin user created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Admin user created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Admin access required"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function createAdminUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,super_admin'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
            'loyalty_points' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin user created successfully',
            'data' => $user->makeHidden(['password'])
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/orders",
     *     tags={"Admin"},
     *     summary="Get all orders",
     *     description="Returns paginated list of all orders for admin management",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of orders",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Admin access required")
     * )
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user:id,name,email', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/orders/{orderId}/status",
     *     tags={"Admin"},
     *     summary="Update order status",
     *     description="Update the status of an order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="processing"),
     *             @OA\Property(property="tracking_number", type="string", nullable=true, example="TRK123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order status updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Admin access required"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|string',
            'tracking_number' => 'nullable|string'
        ]);

        $order = Order::findOrFail($orderId);
        
        $updateData = ['status' => $request->status];
        
        if ($request->has('tracking_number')) {
            $updateData['tracking_number'] = $request->tracking_number;
        }
        
        if ($request->status === 'shipped') {
            $updateData['shipped_at'] = now();
        }
        
        $order->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
    }

    // ===== PRODUCT MANAGEMENT =====

    /**
     * @OA\Post(
     *     path="/api/admin/products",
     *     tags={"Admin"},
     *     summary="Create new product",
     *     description="Create a new product in the catalog",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","category_id","price","description"},
     *             @OA\Property(property="name", type="string", example="Hydrating Face Serum"),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="price", type="number", format="float", example=29.99),
     *             @OA\Property(property="sale_price", type="number", format="float", example=24.99),
     *             @OA\Property(property="description", type="string", example="Nourishing serum for all skin types"),
     *             @OA\Property(property="ingredients", type="string", example="Hyaluronic acid, Vitamin E"),
     *             @OA\Property(property="skin_type", type="string", example="all"),
     *             @OA\Property(property="stock_quantity", type="integer", example=100),
     *             @OA\Property(property="is_featured", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function createProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'description' => 'required|string',
            'ingredients' => 'nullable|string',
            'skin_type' => 'nullable|string',
            'stock_quantity' => 'required|integer|min:0',
            'is_featured' => 'boolean'
        ]);

        $product = Product::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product->load('category')
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/products/{productId}",
     *     tags={"Admin"},
     *     summary="Update product",
     *     description="Update an existing product",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="stock_quantity", type="integer"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Product updated successfully")
     * )
     */
    public function updateProduct(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        
        $request->validate([
            'name' => 'string|max:255',
            'category_id' => 'exists:categories,id',
            'price' => 'numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean'
        ]);

        $product->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product->fresh()->load('category')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/products/{productId}",
     *     tags={"Admin"},
     *     summary="Delete product",
     *     description="Delete a product from the catalog",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Product deleted successfully")
     * )
     */
    public function deleteProduct($productId)
    {
        $product = Product::findOrFail($productId);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/products/{productId}/reviews",
     *     tags={"Admin"},
     *     summary="Get product reviews for moderation",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Product reviews")
     * )
     */
    public function productReviews($productId)
    {
        $reviews = ProductReview::with(['user:id,name', 'images'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/reviews/{reviewId}/moderate",
     *     tags={"Admin"},
     *     summary="Moderate product review",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"is_approved"},
     *             @OA\Property(property="is_approved", type="boolean", example=true),
     *             @OA\Property(property="admin_notes", type="string", example="Approved - helpful review")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Review moderation updated")
     * )
     */
    public function moderateReview(Request $request, $reviewId)
    {
        $request->validate([
            'is_approved' => 'required|boolean',
            'admin_notes' => 'nullable|string'
        ]);

        $review = ProductReview::findOrFail($reviewId);
        $review->update([
            'is_approved' => $request->is_approved,
            'admin_notes' => $request->admin_notes,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review moderation updated successfully'
        ]);
    }

    // ===== CATEGORY MANAGEMENT =====

    /**
     * @OA\Post(
     *     path="/api/admin/categories",
     *     tags={"Admin"},
     *     summary="Create new category",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Skincare"),
     *             @OA\Property(property="description", type="string", example="Skincare products and treatments"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="image", type="string", example="skincare-category.jpg"),
     *             @OA\Property(property="is_featured", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Category created successfully")
     * )
     */
    public function createCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string',
            'is_featured' => 'boolean'
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category->load('parent', 'children')
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/categories/{categoryId}",
     *     tags={"Admin"},
     *     summary="Update category",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Category updated successfully")
     * )
     */
    public function updateCategory(Request $request, $categoryId)
    {
        $category = Category::findOrFail($categoryId);
        
        $request->validate([
            'name' => 'string|max:255|unique:categories,name,' . $categoryId,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_featured' => 'boolean'
        ]);

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->fresh()->load('parent', 'children')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/categories/{categoryId}",
     *     tags={"Admin"},
     *     summary="Delete category",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Category deleted successfully")
     * )
     */
    public function deleteCategory($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        
        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing products'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    // ===== BEAUTY CONTENT MANAGEMENT =====

    /**
     * @OA\Post(
     *     path="/api/admin/beauty/tips",
     *     tags={"Admin"},
     *     summary="Create beauty tip",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","content","skin_type"},
     *             @OA\Property(property="title", type="string", example="Morning Skincare Routine"),
     *             @OA\Property(property="content", type="string", example="Start your day with these essential steps..."),
     *             @OA\Property(property="skin_type", type="string", example="oily"),
     *             @OA\Property(property="image", type="string", example="skincare-tip.jpg"),
     *             @OA\Property(property="is_featured", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Beauty tip created successfully")
     * )
     */
    public function createBeautyTip(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'skin_type' => 'required|string',
            'image' => 'nullable|string',
            'is_featured' => 'boolean'
        ]);

        $tip = BeautyTip::create([
            ...$request->all(),
            'author_id' => $request->user()->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Beauty tip created successfully',
            'data' => $tip->load('author:id,name')
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/beauty/tips/{tipId}",
     *     tags={"Admin"},
     *     summary="Update beauty tip",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Beauty tip updated successfully")
     * )
     */
    public function updateBeautyTip(Request $request, $tipId)
    {
        $tip = BeautyTip::findOrFail($tipId);
        
        $request->validate([
            'title' => 'string|max:255',
            'content' => 'string',
            'skin_type' => 'string',
            'is_featured' => 'boolean'
        ]);

        $tip->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Beauty tip updated successfully',
            'data' => $tip->fresh()
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/beauty/tips/{tipId}",
     *     tags={"Admin"},
     *     summary="Delete beauty tip",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Beauty tip deleted successfully")
     * )
     */
    public function deleteBeautyTip($tipId)
    {
        $tip = BeautyTip::findOrFail($tipId);
        $tip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Beauty tip deleted successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/beauty/videos",
     *     tags={"Admin"},
     *     summary="Create tutorial video",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=201, description="Tutorial video created successfully")
     * )
     */
    public function createTutorialVideo(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video_url' => 'required|url',
            'thumbnail' => 'nullable|string',
            'duration' => 'nullable|integer',
            'category' => 'nullable|string',
            'is_featured' => 'boolean'
        ]);

        $video = TutorialVideo::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tutorial video created successfully',
            'data' => $video
        ], 201);
    }

    // ===== COMMUNITY MODERATION =====

    /**
     * @OA\Get(
     *     path="/api/admin/community/posts",
     *     tags={"Admin"},
     *     summary="Get community posts for moderation",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (pending, approved, rejected)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Community posts")
     * )
     */
    public function getCommunityPosts(Request $request)
    {
        $query = CommunityPost::with(['user:id,name', 'comments']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('reported')) {
            $query->where('is_reported', true);
        }

        $posts = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/community/posts/{postId}/moderate",
     *     tags={"Admin"},
     *     summary="Moderate community post",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action"},
     *             @OA\Property(property="action", type="string", enum={"approve","reject","feature"}, example="approve"),
     *             @OA\Property(property="reason", type="string", example="Approved - follows community guidelines")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Post moderation updated")
     * )
     */
    public function moderateCommunityPost(Request $request, $postId)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,feature',
            'reason' => 'nullable|string'
        ]);

        $post = CommunityPost::findOrFail($postId);

        $updateData = [
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
            'moderation_reason' => $request->reason
        ];

        switch ($request->action) {
            case 'approve':
                $updateData['status'] = 'approved';
                break;
            case 'reject':
                $updateData['status'] = 'rejected';
                break;
            case 'feature':
                $updateData['status'] = 'approved';
                $updateData['is_featured'] = true;
                break;
        }

        $post->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Post moderation updated successfully'
        ]);
    }

    // ===== SUPPORT MANAGEMENT =====

    /**
     * @OA\Post(
     *     path="/api/admin/support/faqs",
     *     tags={"Admin"},
     *     summary="Create FAQ entry",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question","answer","category"},
     *             @OA\Property(property="question", type="string", example="How do I return a product?"),
     *             @OA\Property(property="answer", type="string", example="You can return products within 30 days..."),
     *             @OA\Property(property="category", type="string", example="returns"),
     *             @OA\Property(property="is_featured", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="FAQ created successfully")
     * )
     */
    public function createFAQ(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'required|string',
            'is_featured' => 'boolean'
        ]);

        $faq = FAQ::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully',
            'data' => $faq
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/support/tickets",
     *     tags={"Admin"},
     *     summary="Get support tickets",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Support tickets")
     * )
     */
    public function getSupportTickets(Request $request)
    {
        $query = SupportTicket::with(['user:id,name,email', 'messages']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/support/tickets/{ticketId}",
     *     tags={"Admin"},
     *     summary="Update support ticket",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Ticket updated successfully")
     * )
     */
    public function updateSupportTicket(Request $request, $ticketId)
    {
        $request->validate([
            'status' => 'in:open,in_progress,resolved,closed',
            'priority' => 'in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Ticket updated successfully',
            'data' => $ticket->fresh()
        ]);
    }

    // ===== STORE MANAGEMENT =====

    /**
     * @OA\Post(
     *     path="/api/admin/stores",
     *     tags={"Admin"},
     *     summary="Create store location",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=201, description="Store created successfully")
     * )
     */
    public function createStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string',
            'phone' => 'nullable|string',
            'hours' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        $store = Store::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Store created successfully',
            'data' => $store
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/stores/{storeId}",
     *     tags={"Admin"},
     *     summary="Update store location",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Store updated successfully")
     * )
     */
    public function updateStore(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);
        
        $request->validate([
            'name' => 'string|max:255',
            'address' => 'string',
            'city' => 'string',
            'phone' => 'nullable|string',
            'hours' => 'nullable|string'
        ]);

        $store->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Store updated successfully',
            'data' => $store->fresh()
        ]);
    }
}
