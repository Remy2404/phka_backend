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

class UserController extends Controller
{
    /**
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