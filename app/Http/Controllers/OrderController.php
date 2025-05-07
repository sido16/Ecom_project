<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $user = Auth::user();
        $cart = Order::where('user_id', $user->id)->where('is_validated', false)->first();

        if (!$cart) {
            Log::info('Création d\'un nouveau panier pour user_id=' . $user->id);
            $cart = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => 0,
            ]);
        }

        $existingOrderProduct = OrderProduct::where('order_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingOrderProduct) {
            $existingOrderProduct->update([
                'quantity' => $validated['quantity'],
            ]);
            Log::info("Quantité remplacée pour product_id={$product->id}, nouvelle quantité={$validated['quantity']}");
        } else {
            $orderProduct = OrderProduct::create([
                'order_id' => $cart->id,
                'product_id' => $product->id,
                'supplier_id' => $product->supplier_id,
                'quantity' => $validated['quantity'],
                'unit_price' => $product->price ?? 0,
            ]);
            Log::info("Nouveau produit ajouté au panier: product_id={$product->id}, quantity={$validated['quantity']}");
        }

        $cart->update([
            'total_amount' => $cart->orderProducts->sum(fn($item) => $item->quantity * $item->unit_price),
        ]);

        return response()->json(['message' => 'Product added to cart', 'order_id' => $cart->id]);
    }

    public function getCart()
    {
        try {
            $user = Auth::user();
            $cart = Order::where('user_id', $user->id)
                ->where('is_validated', false)
                ->with(['orderProducts.product.pictures'])
                ->first();

            if (!$cart) {
                Log::info('getCart: Aucun panier trouvé pour user_id=' . $user->id);
                return response()->json(['message' => 'Cart not found'], 404);
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

            Log::info('getCart: Panier chargé avec succès pour user_id=' . $user->id, ['cart_id' => $cart->id]);
            return response()->json(['data' => $cartData]);
        } catch (\Exception $e) {
            Log::error('getCart: Erreur lors de la récupération du panier', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la récupération du panier'], 500);
        }
    }

    public function removeFromCart(Request $request, $itemId)
    {
        try {
            $user = Auth::user();
            $cart = Order::where('user_id', $user->id)->where('is_validated', false)->first();

            if (!$cart) {
                Log::info('removeFromCart: Aucun panier trouvé pour user_id=' . $user->id);
                return response()->json(['message' => 'Cart not found'], 404);
            }

            $orderProduct = OrderProduct::where('id', $itemId)
                ->where('order_id', $cart->id)
                ->first();

            if (!$orderProduct) {
                Log::warning('removeFromCart: Article non trouvé dans le panier', [
                    'itemId' => $itemId,
                    'order_id' => $cart->id,
                ]);
                return response()->json(['message' => 'Item not found in cart'], 404);
            }

            $orderProduct->delete();
            Log::info("Article supprimé du panier: item_id={$itemId}, order_id={$cart->id}");

            $cart->update([
                'total_amount' => $cart->orderProducts->sum(fn($item) => $item->quantity * $item->unit_price),
            ]);

            return response()->json(['message' => 'Item removed from cart']);
        } catch (\Exception $e) {
            Log::error('removeFromCart: Erreur lors de la suppression de l\'article', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la suppression de l\'article'], 500);
        }
    }

    public function updateCartItem(Request $request, $itemId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $user = Auth::user();
            $cart = Order::where('user_id', $user->id)->where('is_validated', false)->first();

            if (!$cart) {
                Log::info('updateCartItem: Aucun panier trouvé pour user_id=' . $user->id);
                return response()->json(['message' => 'Cart not found'], 404);
            }

            $orderProduct = OrderProduct::where('id', $itemId)
                ->where('order_id', $cart->id)
                ->first();

            if (!$orderProduct) {
                Log::warning('updateCartItem: Article non trouvé dans le panier', [
                    'itemId' => $itemId,
                    'order_id' => $cart->id,
                ]);
                return response()->json(['message' => 'Item not found in cart'], 404);
            }

            $orderProduct->update(['quantity' => $validated['quantity']]);
            Log::info("Quantité mise à jour: item_id={$itemId}, nouvelle quantité={$validated['quantity']}");

            $cart->update([
                'total_amount' => $cart->orderProducts->sum(fn($item) => $item->quantity * $item->unit_price),
            ]);

            return response()->json(['message' => 'Item updated in cart']);
        } catch (\Exception $e) {
            Log::error('updateCartItem: Erreur lors de la mise à jour de l\'article', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la mise à jour de l\'article'], 500);
        }
    }

    public function validateCart()
    {
        try {
            $user = Auth::user();
            $cart = Order::where('user_id', $user->id)->where('is_validated', false)->first();

            if (!$cart) {
                Log::info('validateCart: Aucun panier trouvé pour user_id=' . $user->id);
                return response()->json(['message' => 'Cart not found'], 404);
            }

            foreach ($cart->orderProducts as $orderProduct) {
                $product = Product::find($orderProduct->product_id);
                if (!$product || $product->quantity < $orderProduct->quantity) {
                    Log::warning('validateCart: Produit non disponible ou quantité insuffisante', [
                        'product_id' => $orderProduct->product_id,
                        'requested_quantity' => $orderProduct->quantity,
                        'available_quantity' => $product ? $product->quantity : 0,
                    ]);
                    return response()->json(['message' => 'Produit non disponible ou quantité insuffisante'], 400);
                }
            }

            $cart->update(['is_validated' => true]);
            Log::info('validateCart: Panier validé pour user_id=' . $user->id, ['cart_id' => $cart->id]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('validateCart: Erreur lors de la validation du panier', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la validation du panier'], 500);
        }
    }
}
