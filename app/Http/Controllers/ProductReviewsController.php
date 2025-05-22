<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class ProductReviewsController extends Controller
{
    /**
 * @OA\Get(
 *     path="/api/products/{productId}/reviews",
 *     summary="Get product reviews",
 *     description="Fetches all reviews for a specific product, including rating statistics.",
 *     operationId="getProductReviews",
 *     tags={"Product Reviews"},
 *     @OA\Parameter(
 *         name="productId",
 *         in="path",
 *         required=true,
 *         description="ID of the product",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of product reviews and rating statistics",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="reviews", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="total_rating", type="integer", example=45),
 *                 @OA\Property(property="average_rating", type="number", format="float", example=4.5),
 *                 @OA\Property(property="review_count", type="integer", example=10)
 *             ),
 *             @OA\Property(property="message", type="string", example="Reviews retrieved successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Product not found"
 *     )
 * )
 */

    public function index($productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $reviews = ProductReview::with(['user', 'replyUser'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = ProductReview::where('product_id', $productId)
            ->whereNotNull('rating')
            ->selectRaw('SUM(rating) as total_rating, AVG(rating) as average_rating, COUNT(*) as review_count')
            ->first();

        return response()->json([
            'data' => [
                'reviews' => $reviews,
                'total_rating' => $stats->total_rating ? (int)$stats->total_rating : 0,
                'average_rating' => $stats->average_rating ? round($stats->average_rating, 2) : 0,
                'review_count' => $stats->review_count ? (int)$stats->review_count : 0,
            ],
            'message' => 'Reviews retrieved successfully',
        ], 200);
    }

    /**
 * @OA\Post(
 *     path="/api/products/{productId}/reviews",
 *     summary="Submit a product review",
 *     description="Allows a user to submit a review for a product. Suppliers cannot review their own products.",
 *     operationId="submitProductReview",
 *     tags={"Product Reviews"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="productId",
 *         in="path",
 *         required=true,
 *         description="ID of the product",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"rating", "comment"},
 *             @OA\Property(property="rating", type="integer", example=4, description="Rating between 1 and 5"),
 *             @OA\Property(property="comment", type="string", example="Excellent product!", description="Review comment")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Review submitted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Review submitted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Suppliers cannot review their own products"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed"
 *     )
 * )
 */

    public function storeReview(Request $request, $productId): JsonResponse
    {
        \Log::info('Store Product Review Request:', [
            'productId' => $productId,
            'input' => $request->all(),
            'user' => Auth::user() ? Auth::user()->toArray() : null,
        ]);

        try {
            $product = Product::findOrFail($productId);
            $user = Auth::user();

            if (!$user) {
                \Log::warning('No authenticated user found for product review', ['productId' => $productId]);
                return response()->json([
                    'message' => 'Unauthorized',
                    'errors' => ['user' => 'No authenticated user found'],
                ], 401);
            }

            // Check supplier ownership via supplier_id
            $supplier = Supplier::find($product->supplier_id);
            if (!$supplier) {
                \Log::error('Supplier not found for product', [
                    'productId' => $productId,
                    'supplierId' => $product->supplier_id,
                ]);
                return response()->json([
                    'message' => 'Invalid product supplier',
                    'errors' => ['product_id' => 'Supplier not found'],
                ], 422);
            }

            if ($supplier->user_id === $user->id) {
                \Log::warning('User attempted to review own product', [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'supplier_id' => $product->supplier_id,
                    'supplier_user_id' => $supplier->user_id,
                ]);
                return response()->json([
                    'message' => 'You cannot review your own product',
                    'errors' => ['product_id' => 'Suppliers cannot review their own products'],
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|between:1,5',
                'comment' => 'required|string|min:10|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $review = ProductReview::create([
                'product_id' => $productId,
                'user_id' => $user->id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return response()->json([
                'data' => $review->load('user'),
                'message' => 'Review submitted successfully',
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Store Product Review Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'productId' => $productId,
            ]);

            return response()->json([
                'message' => 'An error occurred while processing your request',
                'errors' => ['server' => $e->getMessage()],
            ], 500);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/products/{productId}/reviews/{reviewId}/reply",
 *     summary="Reply to a product review",
 *     description="Allows a supplier to reply to a product review. Only one reply is allowed per review.",
 *     operationId="replyToReview",
 *     tags={"Product Reviews"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="productId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="reviewId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=5)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"comment"},
 *             @OA\Property(property="comment", type="string", example="Thank you for your feedback!", description="Reply to the review")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Reply submitted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Reply submitted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Only the supplier can reply to reviews"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed or reply already exists"
 *     )
 * )
 */

    public function storeReply(Request $request, $productId, $reviewId): JsonResponse
    {
        \Log::info('Store Product Reply Request:', [
            'productId' => $productId,
            'reviewId' => $reviewId,
            'input' => $request->all(),
            'user' => Auth::user() ? Auth::user()->toArray() : null,
        ]);

        try {
            $product = Product::findOrFail($productId);
            $review = ProductReview::where('product_id', $productId)
                ->findOrFail($reviewId);
            $user = Auth::user();

            if (!$user) {
                \Log::warning('No authenticated user found for product reply', [
                    'productId' => $productId,
                    'reviewId' => $reviewId,
                ]);
                return response()->json([
                    'message' => 'Unauthorized',
                    'errors' => ['user' => 'No authenticated user found'],
                ], 401);
            }

            // Check supplier ownership via supplier_id
            $supplier = Supplier::find($product->supplier_id);
            if (!$supplier) {
                \Log::error('Supplier not found for product', [
                    'productId' => $productId,
                    'supplierId' => $product->supplier_id,
                ]);
                return response()->json([
                    'message' => 'Invalid product supplier',
                    'errors' => ['product_id' => 'Supplier not found'],
                ], 422);
            }

            if ($supplier->user_id !== $user->id) {
                \Log::warning('Unauthorized reply attempt', [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'supplier_id' => $product->supplier_id,
                    'supplier_user_id' => $supplier->user_id,
                ]);
                return response()->json([
                    'message' => 'Only the product supplier can reply to reviews',
                    'errors' => ['user_id' => 'Unauthorized to reply'],
                ], 403);
            }

            if ($review->reply !== null) {
                return response()->json([
                    'message' => 'A reply already exists for this review',
                    'errors' => ['reply' => 'Only one reply is allowed per review'],
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'comment' => 'required|string|min:10|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $review->update([
                'reply' => $request->comment,
                'reply_user_id' => $user->id,
                'reply_created_at' => now(),
            ]);

            return response()->json([
                'data' => $review->load(['user', 'replyUser']),
                'message' => 'Reply submitted successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Store Product Reply Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'productId' => $productId,
                'reviewId' => $reviewId,
            ]);

            return response()->json([
                'message' => 'An error occurred while processing your request',
                'errors' => ['server' => $e->getMessage()],
            ], 500);
        }
    }

    /**
 * @OA\Delete(
 *     path="/api/products/{productId}/reviews/{reviewId}",
 *     summary="Delete a product review",
 *     description="Allows a supplier or admin to delete a review for their product.",
 *     operationId="deleteProductReview",
 *     tags={"Product Reviews"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="productId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="reviewId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Review deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Review deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Only suppliers or admins can delete reviews"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Product or review not found"
 *     )
 * )
 */

    public function destroy($productId, $reviewId): JsonResponse
    {
        \Log::info('Delete Product Review Request:', [
            'productId' => $productId,
            'reviewId' => $reviewId,
            'user' => Auth::user() ? Auth::user()->toArray() : null,
        ]);

        try {
            $product = Product::findOrFail($productId);
            $review = ProductReview::where('product_id', $productId)->findOrFail($reviewId);
            $user = Auth::user();

            if (!$user) {
                \Log::warning('No authenticated user found for product review deletion', [
                    'productId' => $productId,
                    'reviewId' => $reviewId,
                ]);
                return response()->json([
                    'message' => 'Unauthorized',
                    'errors' => ['user' => 'No authenticated user found'],
                ], 401);
            }

            // Check supplier ownership via supplier_id
            $supplier = Supplier::find($product->supplier_id);
            if (!$supplier) {
                \Log::error('Supplier not found for product', [
                    'productId' => $productId,
                    'supplierId' => $product->supplier_id,
                ]);
                return response()->json([
                    'message' => 'Invalid product supplier',
                    'errors' => ['product_id' => 'Supplier not found'],
                ], 422);
            }

            if ($supplier->user_id !== $user->id && !$user->hasRole('admin')) {
                \Log::warning('Unauthorized delete attempt', [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'supplier_id' => $product->supplier_id,
                    'supplier_user_id' => $supplier->user_id,
                ]);
                return response()->json([
                    'message' => 'Unauthorized to delete review',
                    'errors' => ['user_id' => 'Only suppliers or admins can delete reviews'],
                ], 403);
            }

            $review->delete();

            return response()->json([
                'message' => 'Review deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Delete Product Review Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'productId' => $productId,
                'reviewId' => $reviewId,
            ]);

            return response()->json([
                'message' => 'An error occurred while processing your request',
                'errors' => ['server' => $e->getMessage()],
            ], 500);
        }
    }
}
