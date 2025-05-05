<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders/buy-now",
     *     summary="Buy Now",
     *     description="Creates a validated order immediately for a single product, bypassing the cart.",
     *     operationId="buyNow",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_id", type="integer", description="The ID of the product to purchase", example=1),
     *             @OA\Property(property="quantity", type="integer", description="The quantity to purchase", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order created successfully"),
     *             @OA\Property(property="order_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The product_id field is required"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function buyNow(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        return DB::transaction(function () use ($request, $product) {
            $order = Order::create([
                'user_id' => Auth::id(),
                'status' => 'pending',
                'total_amount' => $product->price * $request->quantity,
                'is_validated' => true,
            ]);

            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'supplier_id' => $product->supplier_id,
                'quantity' => $request->quantity,
                'unit_price' => $product->price,
            ]);

            $product->decrement('quantity', $request->quantity);

            return response()->json(['message' => 'Order created successfully', 'order_id' => $order->id], 201);
        });
    }

    /**
     * @OA\Post(
     *     path="/api/orders/add-to-cart",
     *     summary="Add to Cart",
     *     description="Adds a product to an unvalidated order (cart). If an unvalidated order exists, adds the product to it; otherwise, creates a new order.",
     *     operationId="addToCart",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_id", type="integer", description="The ID of the product to add", example=1),
     *             @OA\Property(property="quantity", type="integer", description="The quantity to add", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product added to cart",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product added to cart"),
     *             @OA\Property(property="order_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The product_id field is required"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        return DB::transaction(function () use ($request, $product) {
            $order = Order::where('user_id', Auth::id())
                ->where('is_validated', false)
                ->first();

            if (!$order) {
                $order = Order::create([
                    'user_id' => Auth::id(),
                    'status' => 'pending',
                    'total_amount' => $product->price * $request->quantity,
                    'is_validated' => false,
                ]);
            } else {
                $order->total_amount += $product->price * $request->quantity;
                $order->save();
            }

            $orderProduct = OrderProduct::where('order_id', $order->id)
                ->where('product_id', $product->id)
                ->first();

            if ($orderProduct) {
                $orderProduct->quantity += $request->quantity;
                $orderProduct->save();
            } else {
                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'supplier_id' => $product->supplier_id,
                    'quantity' => $request->quantity,
                    'unit_price' => $product->price,
                ]);
            }

            return response()->json(['message' => 'Product added to cart', 'order_id' => $order->id], 201);
        });
    }

    /**
     * @OA\Put(
     *     path="/api/orders/{orderId}/validate",
     *     summary="Validate Cart",
     *     description="Validates an unvalidated cart (order) by setting it to validated and updating its status to processing.",
     *     operationId="validateCart",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="The ID of the order to validate",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart validated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cart validated successfully"),
     *             @OA\Property(property="order_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found or not eligible for validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     )
     * )
     */
    public function validateCart(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
         if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }
    
        if ($order->is_validated) {
                return response()->json(['message' => 'Order already validated', 'order_id' => $order->id], 200);
            }
        return DB::transaction(function () use ($order) {
            $order->update([
                'is_validated' => true,
                'status' => 'processing',
            ]);

            return response()->json(['message' => 'Cart validated successfully', 'order_id' => $order->id], 200);
        });
    }

    
    /**
 * @OA\Get(
 *     path="/api/orders/cart",
 *     summary="Get User Cart",
 *     description="Retrieves the authenticated user's current cart with items.",
 *     operationId="getUserCart",
 *     tags={"Cart"},
 *     @OA\Response(
 *         response=200,
 *         description="Cart retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="total_price", type="number", example=35.00),
 *                 @OA\Property(property="items", type="array", @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="product_id", type="integer", example=1),
 *                     @OA\Property(property="quantity", type="integer", example=2),
 *                     @OA\Property(property="price", type="number", example=10.00),
 *                     @OA\Property(property="product", type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="name", type="string", example="Red T-shirt"),
 *                         @OA\Property(property="pictures", type="array", @OA\Items(
 *                             @OA\Property(property="id", type="integer", example=1),
 *                             @OA\Property(property="picture", type="string", example="/storage/product_pictures/red1.jpg")
 *                         ))
 *                     )
 *                 ))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Cart not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Cart not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to retrieve cart"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function getCart(Request $request)
{
    try {
        $user = Auth::user();
        $cart = Order::where('user_id', $user->id)
            ->where('is_validated', 'false')
            ->with(['orderProducts.product.pictures'])
            ->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }

        return response()->json(['data' => $cart], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to retrieve cart',
            'error' => 'Database error occurred'
        ], 500);
    }
}
}