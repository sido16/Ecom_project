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
use App\Models\Supplier;

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
 *     description="Retrieves all unvalidated cart orders for the authenticated user, grouped by supplier.",
 *     operationId="getUserCart",
 *     tags={"Cart"},
 *     @OA\Response(
 *         response=200,
 *         description="Cart retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Cart retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=3),
 *                     @OA\Property(property="user_id", type="integer", example=2),
 *                     @OA\Property(property="supplier_id", type="integer", example=1),
 *                     @OA\Property(property="wilaya_id", type="integer", example=1),
 *                     @OA\Property(property="commune_id", type="integer", example=3),
 *                     @OA\Property(property="full_name", type="string", example="Ahmed Benali"),
 *                     @OA\Property(property="phone_number", type="string", example="+213661234567"),
 *                     @OA\Property(property="address", type="string", example="123 Rue El Mokrani, Algiers"),
 *                     @OA\Property(property="status", type="string", example="processing"),
 *                     @OA\Property(property="order_date", type="string", format="date-time", example="2025-05-16T14:38:14.000000Z"),
 *                     @OA\Property(property="is_validated", type="boolean", example=false),
 *                     @OA\Property(property="created_at", type="string", format="date-time"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time"),
 *                     @OA\Property(
 *                         property="order_products",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(property="id", type="integer", example=1),
 *                             @OA\Property(property="order_id", type="integer", example=3),
 *                             @OA\Property(property="product_id", type="integer", example=1),
 *                             @OA\Property(property="quantity", type="integer", example=4),
 *                             @OA\Property(property="unit_price", type="string", example="333.00"),
 *                             @OA\Property(
 *                                 property="product",
 *                                 type="object",
 *                                 @OA\Property(property="id", type="integer", example=1),
 *                                 @OA\Property(property="supplier_id", type="integer", example=1),
 *                                 @OA\Property(property="category_id", type="integer", example=1),
 *                                 @OA\Property(property="name", type="string", example="Smartphone"),
 *                                 @OA\Property(property="price", type="string", example="333.00"),
 *                                 @OA\Property(property="description", type="string", example="good"),
 *                                 @OA\Property(property="visibility", type="string", example="public"),
 *                                 @OA\Property(property="quantity", type="integer", example=20),
 *                                 @OA\Property(property="minimum_quantity", type="integer", example=10),
 *                                 @OA\Property(property="created_at", type="string", format="date-time"),
 *                                 @OA\Property(property="updated_at", type="string", format="date-time"),
 *                                 @OA\Property(
 *                                     property="pictures",
 *                                     type="array",
 *                                     @OA\Items(
 *                                         type="object",
 *                                         @OA\Property(property="id", type="integer", example=1),
 *                                         @OA\Property(property="product_id", type="integer", example=1),
 *                                         @OA\Property(property="picture", type="string", example="product_pictures/example.png"),
 *                                         @OA\Property(property="created_at", type="string", format="date-time"),
 *                                         @OA\Property(property="updated_at", type="string", format="date-time")
 *                                     )
 *                                 )
 *                             )
 *                         )
 *                     )
 *                 )
 *             )
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

        // Get all unvalidated orders (carts) for this user
        $carts = Order::where('user_id', $user->id)
            ->where('is_validated', false)
            ->with(['orderProducts.product.pictures'])
            ->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty',
                'data'    => []
            ], 200);
        }

        return response()->json([
            'message' => 'Cart retrieved successfully',
            'data'    => $carts
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to retrieve cart',
            'error'   => 'Database error occurred'
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
        'order_id'   => 'required|integer|exists:orders,id',
        'product_id' => 'required|integer|exists:products,id',
        'quantity'   => 'required|integer|min:1',
    ]);
    $order = Order::where('id', $request->order_id)
        ->where('user_id', Auth::id())
        ->where('is_validated', false)
        ->first();

    Log::info('Cart update - Order lookup result', [
        'user_id'   => Auth::id(),
        'order_id'  => $request->order_id,
        'order'     => $order
    ]);

    if (!$order) {
        return response()->json([
            'message' => 'Cart not found or already validated',
        ], 404);
    }

    try {
        return DB::transaction(function () use ($request, $order) {
            $orderProduct = OrderProduct::where('order_id', $order->id)
                ->where('product_id', $request->product_id)
                ->first();

            if (!$orderProduct) {
                return response()->json([
                    'message' => 'Product not found in cart',
                ], 404);
            }

            $orderProduct->quantity = $request->quantity;
            $orderProduct->save();

            return response()->json([
                'message'  => 'Product quantity updated',
                'order_id' => $order->id
            ], 200);
        });
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to update cart',
            'error'   => 'Database error occurred'
        ], 500);
    }
}



/**
 * @OA\Delete(
 *     path="/api/cart/remove/{product_id}",
 *     summary="Remove Product from Cart",
 *     description="Removes a product from the authenticated user's cart. Deletes the entire cart if it becomes empty. Optionally accepts an order_id to specify the cart.",
 *     operationId="removeCartProduct",
 *     tags={"Cart"},
 *     @OA\Parameter(
 *         name="product_id",
 *         in="path",
 *         description="ID of the product to remove",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(property="order_id", type="integer", description="The ID of the order (cart) to remove the product from", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Product removed successfully or cart deleted if empty",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Product removed from cart")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Cannot modify a validated order",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Cannot modify a validated order")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Cart or product not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Cart not found")
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
 public function removeFromCart($product_id, Request $request)
 {
     try {
         return DB::transaction(function () use ($product_id, $request) {
             $order = Order::when($request->order_id, function ($query, $orderId) {
                     return $query->where('id', $orderId);
                 })
                 ->first();
 
             if (!$order || $order->user_id !== Auth::id()) {
                 return response()->json(['message' => 'Cart not found'], 404);
             }
             if ($order->is_validated) {
                return response()->json(['message' => 'Cannot modify a validated order'], 403);
            }
 
             $orderProduct = OrderProduct::where('order_id', $order->id)
                 ->where('product_id', $product_id)
                 ->first();
 
             if (!$orderProduct) {
                 return response()->json(['message' => 'Product not found in cart'], 404);
             }
 
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
 

    /**
 * @OA\Delete(
 *     path="/api/orders/cart/clear",
 *     summary="Clear Cart",
 *     description="Deletes all non-validated orders and their products for the authenticated user.",
 *     operationId="clearCart",
 *     tags={"Cart"},
 *     @OA\Response(
 *         response=200,
 *         description="Cart cleared successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Cart cleared successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No active cart found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="No active cart to clear")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to clear cart"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function clearCart()
{
    try {
        return DB::transaction(function () {
            $orders = Order::where('user_id', Auth::id())
                ->where('is_validated', false)
                ->get();

            if ($orders->isEmpty()) {
                return response()->json(['message' => 'No active cart to clear'], 404);
            }

            foreach ($orders as $order) {
                $order->orderProducts()->delete();
                $order->delete();
            }

            return response()->json(['message' => 'Cart cleared successfully'], 200);
        });
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to clear cart',
            'error' => 'Database error occurred'
        ], 500);
    }
}



/**
 * @OA\Get(
 *     path="/api/supplier-orders/{id}",
 *     summary="Get a validated order by ID",
 *     tags={"Supplier Orders"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Order ID",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Validated order details",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="user_id", type="integer", example=1),
 *             @OA\Property(property="supplier_id", type="integer", example=1),
 *             @OA\Property(property="wilaya_id", type="integer", example=1),
 *             @OA\Property(property="commune_id", type="integer", example=1),
 *             @OA\Property(property="full_name", type="string", example="John Doe"),
 *             @OA\Property(property="phone_number", type="string", example="+213661234567"),
 *             @OA\Property(property="address", type="string", example="123 Main St", nullable=true),
 *             @OA\Property(property="status", type="string", enum={"pending", "processing", "delivered"}, example="pending"),
 *             @OA\Property(property="is_validated", type="boolean", example=true),
 *             @OA\Property(property="created_at", type="string", format="date-time"),
 *             @OA\Property(property="updated_at", type="string", format="date-time"),
 *             @OA\Property(
 *                 property="order_products",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="order_id", type="integer", example=1),
 *                     @OA\Property(property="product_id", type="integer", example=1),
 *                     @OA\Property(property="quantity", type="integer", example=2),
 *                     @OA\Property(property="unit_price", type="number", format="float", example=99.99)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Not authorized to view this order",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Not authorized to view this order")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Validated order not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Validated order not found")
 *         )
 *     )
 * )
 */

public function show($id)
{
    $order = Order::with(['user', 'supplier', 'wilaya', 'commune', 'orderProducts.product.pictures'])
        ->where('id', $id)
        ->where('is_validated', true)
        ->first();

    if (!$order) {
        return response()->json(['message' => 'Validated order not found'], 404);
    }
    if (
        $order->user_id !== Auth::id() &&
        $order->supplier_id !== Auth::user()->suppliers()->first()?->id
    ) {
        return response()->json(['message' => 'Not authorized to view this order'], 403);
    }

    return response()->json($order);
}


/**
 * @OA\Get(
 *     path="/api/supplier-orders/user/{user_id}",
 *     summary="Get all orders made by a user",
 *     tags={"Supplier Orders"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="user_id",
 *         in="path",
 *         required=true,
 *         description="User ID",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of orders",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="supplier_id", type="integer", example=1),
 *                 @OA\Property(property="wilaya_id", type="integer", example=1),
 *                 @OA\Property(property="commune_id", type="integer", example=1),
 *                 @OA\Property(property="full_name", type="string", example="John Doe"),
 *                 @OA\Property(property="phone_number", type="string", example="+213661234567"),
 *                 @OA\Property(property="address", type="string", example="123 Main St", nullable=true),
 *                 @OA\Property(property="status", type="string", enum={"pending", "processing", "delivered"}, example="pending"),
 *                 @OA\Property(property="is_validated", type="boolean", example=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time"),
 *                 @OA\Property(
 *                     property="order_products",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="order_id", type="integer", example=1),
 *                         @OA\Property(property="product_id", type="integer", example=1),
 *                         @OA\Property(property="quantity", type="integer", example=2),
 *                         @OA\Property(property="unit_price", type="number", format="float", example=99.99)
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized access",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthorized access")
 *         )
 *     )
 * )
 */

public function getByUser($user_id)
{
    if (auth()->id() !== (int)$user_id) {
        return response()->json(['message' => 'Unauthorized access'], 403);
    }

    $orders = Order::with(['supplier', 'wilaya', 'commune', 'orderProducts.product.pictures'])
        ->where('user_id', $user_id)
        ->get();

    return response()->json($orders);
}

/**
 * @OA\Get(
 *     path="/api/supplier-orders/supplier/{supplier_id}",
 *     summary="Get all orders received by a supplier",
 *     tags={"Supplier Orders"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="supplier_id",
 *         in="path",
 *         required=true,
 *         description="Supplier ID",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of supplier's orders",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="supplier_id", type="integer", example=1),
 *                 @OA\Property(property="wilaya_id", type="integer", example=1),
 *                 @OA\Property(property="commune_id", type="integer", example=1),
 *                 @OA\Property(property="full_name", type="string", example="John Doe"),
 *                 @OA\Property(property="phone_number", type="string", example="+213661234567"),
 *                 @OA\Property(property="address", type="string", example="123 Main St", nullable=true),
 *                 @OA\Property(property="status", type="string", enum={"pending", "processing", "delivered"}, example="pending"),
 *                 @OA\Property(property="is_validated", type="boolean", example=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time"),
 *                 @OA\Property(
 *                     property="order_products",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="order_id", type="integer", example=1),
 *                         @OA\Property(property="product_id", type="integer", example=1),
 *                         @OA\Property(property="quantity", type="integer", example=2),
 *                         @OA\Property(property="unit_price", type="number", format="float", example=99.99)
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized access or supplier not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthorized access or supplier not found")
 *         )
 *     )
 * )
 */

public function getBySupplier($supplier_id)
{
    $supplier = Supplier::where('id', $supplier_id)
        ->where('user_id', auth()->id())
        ->first();

    if (!$supplier) {
        return response()->json(['message' => 'Unauthorized access or supplier not found'], 403);
    }

    $orders = Order::with(['user', 'wilaya', 'commune', 'orderProducts.product.pictures'])
        ->where('supplier_id', $supplier_id)
        ->get();

    return response()->json($orders);
}

/**
 *     @OA\Patch(
 *     path="/api/orders/{id}/status",
 *     summary="Update order status",
 *     description="Updates the status of a specific order. Only the supplier or the user who placed the order can update it.",
 *     operationId="updateOrderStatus",
 *     tags={"Supplier Orders"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Order ID",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"status"},
 *             @OA\Property(
 *                 property="status",
 *                 type="string",
 *                 enum={"pending", "processing", "delivered"},
 *                 description="New status of the order",
 *                 example="processing"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order status updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Order status updated successfully"),
 *             @OA\Property(
 *                 property="order",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="status", type="string", example="processing"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Not authorized to update this order",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Not authorized to update this order")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Order not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The status field is required"),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="status",
 *                     type="array",
 *                     @OA\Items(type="string", example="The status must be one of: pending, processing, delivered")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:pending,processing,delivered',
    ]);

    $order = Order::find($id);

    if (!$order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    // Allow only the supplier OR user concerned with the order to update it
    $user = Auth::user();

    $userSupplierId = $user->suppliers()->first()?->id;

    if ($order->supplier_id !== $userSupplierId && $order->user_id !== $user->id) {
        return response()->json(['message' => 'Not authorized to update this order'], 403);
    }

    $order->status = $request->input('status');
    $order->save();

    return response()->json([
        'message' => 'Order status updated successfully',
        'order' => $order,
    ]);
}




}