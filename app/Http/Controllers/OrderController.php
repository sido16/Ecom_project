<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


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
 *             required={"product_id", "quantity", "full_name", "phone_number", "wilaya_id", "commune_id"},
 *             @OA\Property(property="product_id", type="integer", description="The ID of the product to purchase", example=1),
 *             @OA\Property(property="quantity", type="integer", description="The quantity to purchase", example=2),
 *             @OA\Property(property="full_name", type="string", description="Buyer's full name", example="Ahmed Benali"),
 *             @OA\Property(property="phone_number", type="string", description="Phone number", example="+213661234567"),
 *             @OA\Property(property="address", type="string", description="Address", example="123 Rue El Mokrani, Algiers", nullable=true),
 *             @OA\Property(property="wilaya_id", type="integer", description="Wilaya ID", example=1),
 *             @OA\Property(property="commune_id", type="integer", description="Commune ID", example=3)
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
 *             @OA\Property(property="message", type="string", example="Validation error"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     )
 * )
 */

public function buyNow(Request $request)
{
    $request->validate([
        'product_id'   => 'required|exists:products,id',
        'quantity'     => 'required|integer|min:1',
        'full_name'    => 'required|string|max:255',
        'phone_number' => 'required|string|max:20',
        'address'      => 'nullable|string|max:255',
        'wilaya_id'    => 'required|exists:wilayas,id',
        'commune_id'   => 'required|exists:communes,id',
    ]);

    $product = Product::findOrFail($request->product_id);

    return DB::transaction(function () use ($request, $product) {
        $order = Order::create([
            'user_id'      => Auth::id(),
            'supplier_id'  => $product->supplier_id,
            'wilaya_id'    => $request->wilaya_id,
            'commune_id'   => $request->commune_id,
            'full_name'    => $request->full_name,
            'phone_number' => $request->phone_number,
            'address'      => $request->address,
            'status'       => 'pending',
            'is_validated' => true,
        ]);

        OrderProduct::create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => $request->quantity,
            'unit_price' => $product->price,
        ]);

        $product->decrement('quantity', $request->quantity);

        return response()->json([
            'message'  => 'Order created successfully',
            'order_id' => $order->id,
        ], 201);
    });
}

/**
 * @OA\Post(
 *     path="/api/orders/add-to-cart",
 *     summary="Add to Cart",
 *     description="Adds a product to an unvalidated order (cart). If an unvalidated order exists for the same supplier, it will be reused; otherwise, a new one is created.",
 *     operationId="addToCart",
 *     tags={"Orders"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"product_id", "quantity"},
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
        'quantity'   => 'required|integer|min:1',
    ]);

    $product = Product::findOrFail($request->product_id);

    return DB::transaction(function () use ($request, $product) {
        $userId = Auth::id();

        // Check for existing unvalidated order for this user AND same supplier
        $order = Order::where('user_id', $userId)
            ->where('supplier_id', $product->supplier_id)
            ->where('is_validated', false)
            ->first();

       

        if (!$order) {
            $order = Order::create([
                'user_id'      => $userId,
                'supplier_id'  => $product->supplier_id,
                'status'       => 'pending',
                'is_validated' => false,
            ]);
        }

        $orderProduct = OrderProduct::where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->first();

        if ($orderProduct) {
            $orderProduct->quantity += $request->quantity;
            $orderProduct->save();
        } else {
            OrderProduct::create([
                'order_id'    => $order->id,
                'product_id'  => $product->id,
                'supplier_id' => $product->supplier_id,
                'quantity'    => $request->quantity,
                'unit_price'  => $product->price,
            ]);
        }

        return response()->json([
            'message'  => 'Product added to cart',
            'order_id' => $order->id
        ], 201);
    });
}

/**
 * @OA\Put(
 *     path="/api/orders/validate-cart",
 *     summary="Validate All Carts",
 *     description="Validates all unvalidated cart orders for the current user by setting them to validated and updating customer information.",
 *     operationId="validateCart",
 *     tags={"Orders"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"full_name", "phone_number", "wilaya_id", "commune_id"},
 *             @OA\Property(property="full_name", type="string", description="Customer's full name", example="Ahmed Benali"),
 *             @OA\Property(property="phone_number", type="string", description="Customer's phone number", example="+213661234567"),
 *             @OA\Property(property="address", type="string", description="Customer's address", example="123 Rue El Mokrani, Algiers", nullable=true),
 *             @OA\Property(property="wilaya_id", type="integer", description="Wilaya ID", example=1),
 *             @OA\Property(property="commune_id", type="integer", description="Commune ID", example=3)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="All carts validated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="All carts validated successfully"),
 *             @OA\Property(property="order_ids", type="array", @OA\Items(type="integer"), example={1, 2})
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No orders to validate",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="No orders to validate")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Validation error"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     )
 * )
 */

    public function validateCart(Request $request)
    {
        $request->validate([
            'full_name'    => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'address'      => 'nullable|string|max:255',
            'wilaya_id'    => 'required|exists:wilayas,id',
            'commune_id'   => 'required|exists:communes,id',
        ]);
    
        $userId = Auth::id();
    
        $orders = Order::where('user_id', $userId)
            ->where('is_validated', false)
            ->get();
    
        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders to validate'], 404);
        }
    
        return DB::transaction(function () use ($orders, $request, $userId) {
            foreach ($orders as $order) {
                $order->update([
                    'full_name'    => $request->full_name,
                    'phone_number' => $request->phone_number,
                    'address'      => $request->address,
                    'wilaya_id'    => $request->wilaya_id,
                    'commune_id'   => $request->commune_id,
                    'is_validated' => true,
                    'status'       => 'processing',
                ]);
    
                Log::info("Order validated for user_id=$userId, order_id={$order->id}");
            }
    
            return response()->json([
                'message'   => 'All carts validated successfully',
                'order_ids' => $orders->pluck('id'),
            ], 200);
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
            ->where('is_validated', false)
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

/**
 * @OA\Put(
 *     path="/api/orders/cart/update",
 *     summary="Update Product Quantity in Cart",
 *     description="Updates the quantity of a product in the authenticated user's cart.",
 *     operationId="updateCartProduct",
 *     tags={"Cart"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="product_id", type="integer", example=1, description="ID of the product"),
 *             @OA\Property(property="quantity", type="integer", example=3, description="New quantity for the product")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Product quantity updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Product quantity updated"),
 *             @OA\Property(property="order_id", type="integer", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Cart or product not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Cart or product not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The product_id field must be an integer"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to update cart"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function updateCart(Request $request)
{
    $request->validate([
        'product_id' => 'required|integer|exists:products,id',
        'quantity' => 'required|integer|min:1',
    ]);

    try {
        return DB::transaction(function () use ($request) {
            $order = Order::where('user_id', Auth::id())
                ->where('is_validated', false)
                ->first();

            if (!$order) {
                return response()->json(['message' => 'Cart not found'], 404);
            }

            $orderProduct = OrderProduct::where('order_id', $order->id)
                ->where('product_id', $request->product_id)
                ->first();

            if (!$orderProduct) {
                return response()->json(['message' => 'Product not found in cart'], 404);
            }

            $product = Product::findOrFail($request->product_id);
            $oldQuantity = $orderProduct->quantity;
            $orderProduct->quantity = $request->quantity;
            $orderProduct->save();

            $order->total_amount += ($request->quantity - $oldQuantity) * $product->price;
            $order->save();

            return response()->json(['message' => 'Product quantity updated', 'order_id' => $order->id], 200);
        });
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to update cart',
            'error' => 'Database error occurred'
        ], 500);
    }
}/**
     * @OA\Delete(
     *     path="/api/orders/cart/remove/{product_id}",
     *     summary="Remove Product from Cart",
     *     description="Removes a product from the authenticated user's cart.",
     *     operationId="removeCartProduct",
     *     tags={"Cart"},
     *     @OA\Parameter(
     *         name="product_id",
     *         in="path",
     *         description="ID of the product to remove",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product removed from cart")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart or product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cart or product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to remove product"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function removeFromCart($product_id)
    {
        try {
            return DB::transaction(function () use ($product_id) {
                $order = Order::where('user_id', Auth::id())
                    ->where('is_validated', false)
                    ->first();

                if (!$order) {
                    return response()->json(['message' => 'Cart not found'], 404);
                }

                $orderProduct = OrderProduct::where('order_id', $order->id)
                    ->where('product_id', $product_id)
                    ->first();

                if (!$orderProduct) {
                    return response()->json(['message' => 'Product not found in cart'], 404);
                }

                $order->total_amount -= $orderProduct->quantity * $orderProduct->unit_price;
                $orderProduct->delete();
                $order->save();

                if ($order->orderProducts()->count() === 0) {
                    $order->delete();
                }

                return response()->json(['message' => 'Product removed from cart'], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove product',
                'error' => 'Database error occurred'
            ], 500);
        }
    }


}