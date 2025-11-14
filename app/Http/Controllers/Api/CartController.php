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
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Cart",
 *     description="Shopping cart management endpoints"
 * )
 */
class CartController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/cart",
     *     tags={"Cart"},
     *     summary="Get user's shopping cart",
     *     description="Returns the user's current shopping cart with items",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Shopping cart details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart", type="object"),
     *                 @OA\Property(property="items_count", type="integer", example=3),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=99.97)
     *             )
     *         )
     *     )
     * )
     *
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
     * @OA\Post(
     *     path="/api/cart/items",
     *     tags={"Cart"},
     *     summary="Add item to cart",
     *     description="Add a product or product variant to the shopping cart",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="variant_id", type="integer", nullable=true, example=5),
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item added to cart successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item added to cart successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart", type="object"),
     *                 @OA\Property(property="item", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Product not found")
     * )
     *
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
     * @OA\Put(
     *     path="/api/cart/items/{itemId}",
     *     tags={"Cart"},
     *     summary="Update cart item quantity",
     *     description="Update the quantity of a specific item in the cart",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="itemId",
     *         in="path",
     *         required=true,
     *         description="Cart item ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart item updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart item updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Cart item not found")
     * )
     *
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
     * @OA\Delete(
     *     path="/api/cart/items/{itemId}",
     *     tags={"Cart"},
     *     summary="Remove item from cart",
     *     description="Remove a specific item from the shopping cart",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="itemId",
     *         in="path",
     *         required=true,
     *         description="Cart item ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item removed from cart successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item removed from cart successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Cart item not found")
     * )
     *
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
     * @OA\Delete(
     *     path="/api/cart/clear",
     *     tags={"Cart"},
     *     summary="Clear entire cart",
     *     description="Remove all items from the shopping cart",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart cleared successfully")
     *         )
     *     )
     * )
     *
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
     * @OA\Get(
     *     path="/api/cart/summary",
     *     tags={"Cart"},
     *     summary="Get cart summary",
     *     description="Get cart summary with totals and item counts",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart summary",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="items_count", type="integer", example=3),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=99.97),
     *                 @OA\Property(property="total_quantity", type="integer", example=5),
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     *
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
     * @OA\Get(
     *     path="/api/cart/validate",
     *     tags={"Cart"},
     *     summary="Validate cart items availability",
     *     description="Check if all cart items are available and in stock",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart validation results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="valid", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="issues",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="item_id", type="integer"),
     *                         @OA\Property(property="type", type="string"),
     *                         @OA\Property(property="message", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     *
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