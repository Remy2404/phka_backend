<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Order management endpoints"
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Get user's orders",
     *     description="Returns paginated list of user's orders with items and addresses",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated orders list",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     *
     * Get user's orders.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::with(['items.product', 'items.variant', 'billingAddress', 'shippingAddress'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{orderId}",
     *     tags={"Orders"},
     *     summary="Get order details",
     *     description="Returns detailed information about a specific order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Order not found")
     * )
     *
     * Get order details.
     */
    public function show(Request $request, $orderId)
    {
        $user = $request->user();

        $order = Order::with([
            'items.product',
            'items.variant',
            'billingAddress',
            'shippingAddress'
        ])
        ->where('user_id', $user->id)
        ->findOrFail($orderId);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Create order from cart",
     *     description="Create a new order from the user's shopping cart",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"billing_address_id", "shipping_address_id", "payment_method"},
     *             @OA\Property(property="billing_address_id", type="integer", example=1),
     *             @OA\Property(property="shipping_address_id", type="integer", example=1),
     *             @OA\Property(property="payment_method", type="string", enum={"credit_card","debit_card","paypal"}, example="credit_card"),
     *             @OA\Property(property="notes", type="string", nullable=true, example="Please handle with care")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Cart is empty or validation failed"),
     *     @OA\Response(response=500, description="Failed to create order")
     * )
     *
     * Create order from cart.
     */
    public function store(CreateOrderRequest $request)
    {
        $user = $request->user();

        // Verify addresses belong to user
        $billingAddress = Address::where('user_id', $user->id)
            ->findOrFail($request->billing_address_id);

        $shippingAddress = Address::where('user_id', $user->id)
            ->findOrFail($request->shipping_address_id);

        // Get user's cart
        $cart = ShoppingCart::with(['items.product', 'items.variant'])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        // Validate cart items
        $validationResult = $this->validateCartItems($cart);
        if (!$validationResult['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Some items in your cart are no longer available',
                'data' => $validationResult
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Calculate totals
            $subtotal = $cart->items->sum(function($item) {
                return $item->quantity * $item->unit_price;
            });

            $taxAmount = $subtotal * 0.08; // 8% tax
            $shippingAmount = $subtotal > 50 ? 0 : 5.99; // Free shipping over $50
            $totalAmount = $subtotal + $taxAmount + $shippingAmount;

            // Generate order number
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(Order::count() + 1, 6, '0', STR_PAD_LEFT);

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'billing_address_id' => $request->billing_address_id,
                'shipping_address_id' => $request->shipping_address_id,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Create order items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'product_name' => $cartItem->product->name,
                    'variant_name' => $cartItem->variant ? $cartItem->variant->name : null,
                    'sku' => $cartItem->variant ? $cartItem->variant->sku : $cartItem->product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'total_price' => $cartItem->quantity * $cartItem->unit_price,
                ]);

                // Update product stock
                if ($cartItem->variant) {
                    $cartItem->variant->decrement('stock_quantity', $cartItem->quantity);
                } else {
                    $cartItem->product->decrement('stock_quantity', $cartItem->quantity);
                }
            }

            // Clear cart
            $cart->items()->delete();

            DB::commit();

            // Load complete order data
            $order->load(['items.product', 'items.variant', 'billingAddress', 'shippingAddress']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/orders/{orderId}/cancel",
     *     tags={"Orders"},
     *     summary="Cancel order",
     *     description="Cancel a pending or processing order and restore stock",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Order not found or cannot be cancelled"),
     *     @OA\Response(response=500, description="Failed to cancel order")
     * )
     *
     * Cancel order.
     */
    public function cancel(Request $request, $orderId)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->findOrFail($orderId);

        DB::beginTransaction();

        try {
            // Restore stock
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::where('id', $item->product_variant_id)
                        ->increment('stock_quantity', $item->quantity);
                } else {
                    Product::where('id', $item->product_id)
                        ->increment('stock_quantity', $item->quantity);
                }
            }

            $order->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{orderId}/tracking",
     *     tags={"Orders"},
     *     summary="Get order tracking information",
     *     description="Returns tracking information for a specific order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order tracking information",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_number", type="string", example="ORD-2024-000001"),
     *                 @OA\Property(property="status", type="string", example="shipped"),
     *                 @OA\Property(property="tracking_number", type="string", nullable=true, example="TRK123456789"),
     *                 @OA\Property(property="shipped_at", type="string", format="datetime", nullable=true),
     *                 @OA\Property(property="delivered_at", type="string", format="datetime", nullable=true),
     *                 @OA\Property(property="estimated_delivery", type="string", format="datetime", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Order not found")
     * )
     *
     * Get order tracking information.
     */
    public function tracking(Request $request, $orderId)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->findOrFail($orderId);

        $tracking = [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'tracking_number' => $order->tracking_number,
            'shipped_at' => $order->shipped_at,
            'delivered_at' => $order->delivered_at,
            'estimated_delivery' => $order->shipped_at ? $order->shipped_at->addDays(3) : null,
        ];

        return response()->json([
            'success' => true,
            'data' => $tracking
        ]);
    }

    /**
     * Validate cart items before creating order.
     */
    private function validateCartItems($cart)
    {
        $issues = [];

        foreach ($cart->items as $item) {
            if (!$item->product->is_active) {
                $issues[] = 'Product "' . $item->product->name . '" is no longer available';
            }

            if ($item->variant) {
                if (!$item->variant->is_active || $item->variant->stock_quantity < $item->quantity) {
                    $issues[] = 'Variant "' . $item->variant->name . '" has insufficient stock';
                }
            } else {
                if ($item->product->stock_quantity < $item->quantity) {
                    $issues[] = 'Product "' . $item->product->name . '" has insufficient stock';
                }
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }
}