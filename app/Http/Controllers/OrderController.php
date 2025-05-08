<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(title="Order API", version="1.0.0")
 */
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
     *     ),
     *     security={{"sanctum": {}}}
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

            Log::info("Commande créée via buy-now pour user_id=" . Auth::id() . ", order_id={$order->id}, product_id={$product->id}, quantity={$request->quantity}");

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
     *     ),
     *     security={{"sanctum": {}}}
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
                    'total_amount' => 0,
                    'is_validated' => false,
                ]);
                Log::info("Création d'un nouveau panier pour user_id=" . Auth::id() . ", order_id={$order->id}");
            }

            $orderProduct = OrderProduct::where('order_id', $order->id)
                ->where('product_id', $product->id)
                ->first();

            if ($orderProduct) {
                $orderProduct->quantity += $request->quantity;
                $orderProduct->save();
                Log::info("Quantité incrémentée pour product_id={$product->id}, nouvelle quantité={$orderProduct->quantity}, order_id={$order->id}");
            } else {
                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'supplier_id' => $product->supplier_id,
                    'quantity' => $request->quantity,
                    'unit_price' => $product->price ?? 0,
                ]);
                Log::info("Nouveau produit ajouté au panier: product_id={$product->id}, quantity={$request->quantity}, order_id={$order->id}");
            }

            $order->update([
                'total_amount' => $order->orderProducts->sum(fn($item) => $item->quantity * $item->unit_price),
            ]);

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
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function validateCart(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($order->is_validated) {
            return response()->json(['message' => 'Order already validated', 'order_id' => $order->id], 200);
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->orderProducts as $orderProduct) {
                $product = Product::find($orderProduct->product_id);
                if (!$product || $product->quantity < $orderProduct->quantity) {
                    return response()->json(['message' => 'Produit non disponible ou quantité insuffisante'], 400);
                }
            }

            $order->update([
                'is_validated' => true,
                'status' => 'processing',
            ]);

            Log::info("Panier validé pour user_id=" . Auth::id() . ", order_id={$order->id}");

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
                ->where('is_validated', false)
                ->with(['orderProducts.product.pictures'])
                ->first();

            if (!$cart) {
                Log::info("Aucun panier trouvé pour user_id=" . $user->id . ", renvoi d'un panier vide");
                return response()->json([
                    'data' => [
                        'id' => null,
                        'total_price' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            $cartData = [
                'id' => $cart->id,
                'total_price' => floatval($cart->total_amount),
                'items' => $cart->orderProducts->map(function ($orderProduct) {
                    return [
                        'id' => $orderProduct->id,
                        'product_id' => $orderProduct->product_id,
                        'quantity' => $orderProduct->quantity,
                        'price' => floatval($orderProduct->unit_price),
                        'product' => [
                            'id' => $orderProduct->product->id,
                            'name' => $orderProduct->product->name,
                            'pictures' => $orderProduct->product->pictures->map(function ($picture) {
                                return [
                                    'id' => $picture->id,
                                    'picture' => $picture->picture,
                                ];
                            }),
                        ],
                    ];
                }),
            ];

            Log::info("Panier chargé avec succès pour user_id=" . $user->id . ", cart_id={$cart->id}");

            return response()->json(['data' => $cartData], 200);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération du panier pour user_id=" . $user->id, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
     *     operationId="updateCart",
     *     tags={"Cart"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="product_id", type="integer", example=1, description="The ID of the product to update"),
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
     *             @OA\Property(property="message", type="string", example="Product not found in cart")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The product_id field is required"),
     *             @OA\Property(property="errors", type="object")
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

            $orderProduct->quantity = $request->quantity;
            $orderProduct->save();

            $order->update([
                'total_amount' => $order->orderProducts->sum(fn($item) => $item->quantity * $item->unit_price),
            ]);

            Log::info("Quantité mise à jour pour product_id={$request->product_id}, nouvelle quantité={$request->quantity}, order_id={$order->id}");

            return response()->json(['message' => 'Product quantity updated', 'order_id' => $order->id], 200);
        });
    }

    /**
     * @OA\Delete(
     *     path="/api/orders/cart/remove/{product_id}",
     *     summary="Remove Product from Cart",
     *     description="Removes a product from the authenticated user's cart.",
     *     operationId="removeFromCart",
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
     *             @OA\Property(property="message", type="string", example=" Sans Serif;Product removed from cart")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart or product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found in cart")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function removeFromCart($product_id)
    {
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

            $orderProduct->delete();

            $order->update([
                'total_amount' => $order->orderProducts->sum(fn($item) => $item->quantity * $item->unit_price),
            ]);

            if ($order->orderProducts()->count() === 0) {
                $order->delete();
                Log::info("Panier vide supprimé pour user_id=" . Auth::id() . ", order_id={$order->id}");
            }

            Log::info("Produit supprimé du panier: product_id={$product_id}, order_id={$order->id}");

            return response()->json(['message' => 'Product removed from cart'], 200);
        });
    }
}
