<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Get user's shopping cart.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $cart = ShoppingCart::with(['items.product', 'items.variant'])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart) {
            $cart = ShoppingCart::create([
                'user_id' => $user->id,
                'session_id' => session()->getId(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $cart,
                'items_count' => $cart->items->count(),
                'total_amount' => $cart->total_amount,
            ]
        ]);
    }

    /**
     * Add item to cart.
     */
    public function addItem(AddToCartRequest $request)
    {
        $user = $request->user();
        $product = Product::findOrFail($request->product_id);

        // Get or create cart
        $cart = ShoppingCart::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'session_id' => session()->getId(),
        ]);

        // Check if item already exists in cart
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->where('product_variant_id', $request->variant_id)
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $request->quantity);
            $item = $existingItem;
        } else {
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'product_variant_id' => $request->variant_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->variant_id ? ProductVariant::find($request->variant_id)->price : $product->price,
            ]);
        }

        // Reload cart with items
        $cart->load(['items.product', 'items.variant']);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully',
            'data' => [
                'cart' => $cart,
                'item' => $item,
            ]
        ]);
    }

    /**
     * Update cart item quantity.
     */
    public function updateItem(UpdateCartItemRequest $request, $itemId)
    {
        $user = $request->user();

        $item = CartItem::whereHas('cart', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($itemId);

        $item->update(['quantity' => $request->quantity]);

        // Reload cart
        $cart = $item->cart->load(['items.product', 'items.variant']);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'data' => [
                'cart' => $cart,
            ]
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(Request $request, $itemId)
    {
        $user = $request->user();

        $item = CartItem::whereHas('cart', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($itemId);

        $cart = $item->cart;
        $item->delete();

        // Reload cart
        $cart->load(['items.product', 'items.variant']);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart successfully',
            'data' => [
                'cart' => $cart,
            ]
        ]);
    }

    /**
     * Clear entire cart.
     */
    public function clearCart(Request $request)
    {
        $user = $request->user();

        $cart = ShoppingCart::where('user_id', $user->id)->first();

        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ]);
    }

    /**
     * Get cart summary.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        $cart = ShoppingCart::with(['items.product', 'items.variant'])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items_count' => 0,
                    'total_amount' => 0,
                    'total_quantity' => 0,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items_count' => $cart->items->count(),
                'total_amount' => $cart->total_amount,
                'total_quantity' => $cart->items->sum('quantity'),
                'items' => $cart->items,
            ]
        ]);
    }

    /**
     * Validate cart items availability.
     */
    public function validateCart(Request $request)
    {
        $user = $request->user();

        $cart = ShoppingCart::with(['items.product', 'items.variant'])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'issues' => [],
                ]
            ]);
        }

        $issues = [];

        foreach ($cart->items as $item) {
            // Check if product is active
            if (!$item->product->is_active) {
                $issues[] = [
                    'item_id' => $item->id,
                    'type' => 'product_inactive',
                    'message' => 'Product is no longer available',
                ];
            }

            // Check stock availability
            if ($item->variant) {
                if (!$item->variant->in_stock || $item->variant->stock_quantity < $item->quantity) {
                    $issues[] = [
                        'item_id' => $item->id,
                        'type' => 'insufficient_stock',
                        'message' => 'Insufficient stock for selected variant',
                    ];
                }
            } else {
                if (!$item->product->in_stock || $item->product->stock_quantity < $item->quantity) {
                    $issues[] = [
                        'item_id' => $item->id,
                        'type' => 'insufficient_stock',
                        'message' => 'Insufficient stock for product',
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => empty($issues),
                'issues' => $issues,
            ]
        ]);
    }
}