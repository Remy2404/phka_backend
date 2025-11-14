<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use App\Models\RecentlyViewed;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="User",
 *     description="User profile and account management endpoints"
 * )
 */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user/profile",
     *     tags={"User"},
     *     summary="Get user profile",
     *     description="Returns detailed user profile with addresses, recent orders, and reviews",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile with statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(
     *                     property="stats",
     *                     type="object",
     *                     @OA\Property(property="total_orders", type="integer", example=5),
     *                     @OA\Property(property="total_reviews", type="integer", example=3),
     *                     @OA\Property(property="loyalty_points", type="integer", example=120),
     *                     @OA\Property(property="wishlist_count", type="integer", example=8)
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * Get user profile with related data.
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load([
            'addresses',
            'orders' => function($query) {
                $query->latest()->limit(5);
            },
            'reviews' => function($query) {
                $query->with('product')->latest()->limit(5);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => [
                    'total_orders' => $user->orders()->count(),
                    'total_reviews' => $user->reviews()->count(),
                    'loyalty_points' => $user->loyalty_points,
                    'wishlist_count' => $user->wishlists()->count(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/user/profile",
     *     tags={"User"},
     *     summary="Update user profile",
     *     description="Update user's profile information",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+1234567890"),
     *             @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-01-01"),
     *             @OA\Property(property="gender", type="string", enum={"male","female","other"}, nullable=true, example="male"),
     *             @OA\Property(property="skin_type", type="string", enum={"normal","dry","oily","combination","sensitive"}, nullable=true, example="combination")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     *
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'skin_type' => 'nullable|in:normal,dry,oily,combination,sensitive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $user->update($request->only([
            'name', 'phone', 'birth_date', 'gender', 'skin_type'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/avatar",
     *     tags={"User"},
     *     summary="Upload user avatar",
     *     description="Upload and update user's avatar image",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"avatar"},
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="Avatar image file (JPEG, PNG, JPG, max 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Avatar updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="avatar_url", type="string", example="http://localhost/storage/avatars/avatar.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     *
     * Upload and update user avatar.
     */
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::exists('public/avatars/' . $user->avatar)) {
            Storage::delete('public/avatars/' . $user->avatar);
        }

        // Store new avatar
        $avatarName = time() . '_' . $user->id . '.' . $request->avatar->extension();
        $request->avatar->storeAs('public/avatars', $avatarName);

        $user->update(['avatar' => $avatarName]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated successfully',
            'data' => [
                'avatar_url' => asset('storage/avatars/' . $avatarName)
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/addresses",
     *     tags={"User"},
     *     summary="Get user's addresses",
     *     description="Returns list of user's addresses sorted by default status and creation date",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user addresses",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     *
     * Get user's addresses.
     */
    public function addresses(Request $request)
    {
        $user = $request->user();

        $addresses = Address::where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/addresses",
     *     tags={"User"},
     *     summary="Create new address",
     *     description="Create a new address for the user",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "first_name", "last_name", "address_line_1", "city", "state", "postal_code", "country"},
     *             @OA\Property(property="type", type="string", enum={"billing","shipping"}, example="shipping"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="company", type="string", nullable=true, example="Acme Corp"),
     *             @OA\Property(property="address_line_1", type="string", example="123 Main St"),
     *             @OA\Property(property="address_line_2", type="string", nullable=true, example="Apt 4B"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="state", type="string", example="NY"),
     *             @OA\Property(property="postal_code", type="string", example="10001"),
     *             @OA\Property(property="country", type="string", example="US"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+1234567890"),
     *             @OA\Property(property="is_default", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Address created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Address created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     *
     * Create new address.
     */
    public function createAddress(StoreAddressRequest $request)
    {
        $user = $request->user();

        // If setting as default, remove default from other addresses
        if ($request->is_default) {
            Address::where('user_id', $user->id)
                ->where('type', $request->type)
                ->update(['is_default' => false]);
        }

        $address = Address::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'company' => $request->company,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'phone' => $request->phone,
            'is_default' => $request->is_default ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully',
            'data' => $address
        ], 201);
    }

    /**
     * Update address.
     */
    public function updateAddress(UpdateAddressRequest $request, $addressId)
    {
        $user = $request->user();

        $address = Address::where('user_id', $user->id)
            ->findOrFail($addressId);

        // If setting as default, remove default from other addresses
        if ($request->is_default) {
            Address::where('user_id', $user->id)
                ->where('type', $address->type)
                ->where('id', '!=', $addressId)
                ->update(['is_default' => false]);
        }

        $address->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => $address
        ]);
    }

    /**
     * Delete address.
     */
    public function deleteAddress(Request $request, $addressId)
    {
        $user = $request->user();

        $address = Address::where('user_id', $user->id)
            ->findOrFail($addressId);

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/wishlist",
     *     tags={"User"},
     *     summary="Get user's wishlist",
     *     description="Returns list of products in user's wishlist",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User's wishlist",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     *
     * Get user's wishlist.
     */
    public function wishlist(Request $request)
    {
        $user = $request->user();

        $wishlist = Wishlist::with(['product.category', 'product.variants'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $wishlist
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/wishlist",
     *     tags={"User"},
     *     summary="Add product to wishlist",
     *     description="Add a product to user's wishlist",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id"},
     *             @OA\Property(property="product_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product added to wishlist",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product added to wishlist"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Product already in wishlist"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     *
     * Add product to wishlist.
     */
    public function addToWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (Wishlist::isInWishlist($user->id, $request->product_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Product already in wishlist'
            ], 400);
        }

        $wishlistItem = Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to wishlist',
            'data' => $wishlistItem->load('product')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/user/wishlist/{productId}",
     *     tags={"User"},
     *     summary="Remove product from wishlist",
     *     description="Remove a product from user's wishlist",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product removed from wishlist",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product removed from wishlist")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Product not found in wishlist")
     * )
     *
     * Remove product from wishlist.
     */
    public function removeFromWishlist(Request $request, $productId)
    {
        $user = $request->user();

        $wishlistItem = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->firstOrFail();

        $wishlistItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from wishlist'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/recently-viewed",
     *     tags={"User"},
     *     summary="Get recently viewed products",
     *     description="Returns list of user's recently viewed products",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Recently viewed products",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     *
     * Get recently viewed products.
     */
    public function recentlyViewed(Request $request)
    {
        $user = $request->user();

        $recentlyViewed = RecentlyViewed::with(['product.category', 'product.variants'])
            ->where('user_id', $user->id)
            ->recent()
            ->limitResults(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $recentlyViewed
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/stats",
     *     tags={"User"},
     *     summary="Get user statistics",
     *     description="Returns comprehensive statistics about user's activity",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_orders", type="integer", example=5),
     *                 @OA\Property(property="total_spent", type="number", format="float", example=299.99),
     *                 @OA\Property(property="total_reviews", type="integer", example=3),
     *                 @OA\Property(property="average_rating", type="number", format="float", example=4.5),
     *                 @OA\Property(property="wishlist_count", type="integer", example=8),
     *                 @OA\Property(property="loyalty_points", type="integer", example=120),
     *                 @OA\Property(property="member_since", type="string", example="Jan 2024")
     *             )
     *         )
     *     )
     * )
     *
     * Get user statistics.
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_orders' => $user->orders()->count(),
            'total_spent' => $user->orders()->where('payment_status', 'paid')->sum('total_amount'),
            'total_reviews' => $user->reviews()->count(),
            'average_rating' => $user->reviews()->avg('rating') ?? 0,
            'wishlist_count' => $user->wishlists()->count(),
            'loyalty_points' => $user->loyalty_points,
            'member_since' => $user->created_at->format('M Y'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}