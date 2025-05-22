<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use App\Models\ServiceProviderReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Service Provider Reviews",
 *     description="API Endpoints for managing service provider reviews"
 * )
 */
class ServiceProviderReviewsController extends Controller
{
    /**
     * Get a list of reviews for a specific service provider.
     *
     * @OA\Get(
     *     path="/api/service-providers/{serviceProviderId}/reviews",
     *     operationId="getServiceProviderReviews",
     *     tags={"Service Provider Reviews"},
     *     summary="List reviews for a service provider",
     *     description="Retrieve all reviews for a given service provider including average and total rating stats.",
     *     @OA\Parameter(
     *         name="serviceProviderId",
     *         in="path",
     *         description="Service Provider ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="reviews", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total_rating", type="integer", example=15),
     *                 @OA\Property(property="average_rating", type="number", format="float", example=3.75),
     *                 @OA\Property(property="review_count", type="integer", example=4),
     *             ),
     *             @OA\Property(property="message", type="string", example="Reviews retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Service provider not found")
     * )
     */
    public function index($serviceProviderId): JsonResponse
    {
        $serviceProvider = ServiceProvider::findOrFail($serviceProviderId);

        $reviews = ServiceProviderReview::with(['user', 'replyUser'])
            ->where('service_provider_id', $serviceProviderId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate total, average, and count
        $stats = ServiceProviderReview::where('service_provider_id', $serviceProviderId)
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
     * Submit a new review for a service provider.
     *
     * @OA\Post(
     *     path="/api/service-providers/{serviceProviderId}/reviews",
     *     operationId="submitServiceProviderReview",
     *     tags={"Service Provider Reviews"},
     *     summary="Submit a review",
     *     description="Allows an authenticated user to submit a review for a service provider, excluding the owner.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="serviceProviderId",
     *         in="path",
     *         description="Service Provider ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating", "comment"},
     *             @OA\Property(property="rating", type="integer", example=5, minimum=1, maximum=5),
     *             @OA\Property(property="comment", type="string", example="Excellent service, highly recommended!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Review successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", example={"id":8, "rating":5, "comment":"Excellent service!", "service_provider_id":1}),
     *             @OA\Property(property="message", type="string", example="Review submitted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Cannot review own service provider"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function storeReview(Request $request, $serviceProviderId): JsonResponse
    {
        \Log::info('Store Service Provider Review Request:', [
            'serviceProviderId' => $serviceProviderId,
            'input' => $request->all(),
            'user' => Auth::user()
        ]);

        try {
            $serviceProvider = ServiceProvider::findOrFail($serviceProviderId);
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'errors' => ['user' => 'No authenticated user found'],
                ], 401);
            }

            if ($serviceProvider->user_id === $user->id) {
                return response()->json([
                    'message' => 'You cannot review your own service provider',
                    'errors' => ['service_provider_id' => 'Owners cannot review their own service provider'],
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

            $review = ServiceProviderReview::create([
                'service_provider_id' => $serviceProviderId,
                'user_id' => $user->id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return response()->json([
                'data' => $review->load('user'),
                'message' => 'Review submitted successfully',
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Store Service Provider Review Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while processing your request',
                'errors' => ['server' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Submit a reply to a specific review (service provider owner only).
     *
     * @OA\Post(
     *     path="/api/service-providers/{serviceProviderId}/reviews/{reviewId}/reply",
     *     operationId="replyToServiceProviderReview",
     *     tags={"Service Provider Reviews"},
     *     summary="Reply to a review",
     *     description="Allows the owner of the service provider to reply to a specific review.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="serviceProviderId",
     *         in="path",
     *         description="Service Provider ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="reviewId",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=8)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"comment"},
     *             @OA\Property(property="comment", type="string", example="Thank you for your kind words!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reply submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", example={"id":8, "reply":"Thank you!"}),
     *             @OA\Property(property="message", type="string", example="Reply submitted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Only service provider owner can reply"),
     *     @OA\Response(response=422, description="Reply already exists or validation error"),
     *     @OA\Response(response=404, description="Service provider or review not found")
     * )
     */
    public function storeReply(Request $request, $serviceProviderId, $reviewId): JsonResponse
    {
        $serviceProvider = ServiceProvider::findOrFail($serviceProviderId);
        $review = ServiceProviderReview::where('service_provider_id', $serviceProviderId)
            ->findOrFail($reviewId);
        $user = Auth::user();

        if ($serviceProvider->user_id !== $user->id) {
            return response()->json([
                'message' => 'Only the service provider owner can reply to reviews',
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
    }

    /**
     * Delete a review (service provider owner or admin only).
     *
     * @OA\Delete(
     *     path="/api/service-providers/{serviceProviderId}/reviews/{reviewId}",
     *     operationId="deleteServiceProviderReview",
     *     tags={"Service Provider Reviews"},
     *     summary="Delete a review",
     *     description="Allows a service provider owner or admin to delete a specific review.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="serviceProviderId",
     *         in="path",
     *         description="Service Provider ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="reviewId",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=8)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized to delete review"),
     *     @OA\Response(response=404, description="Service provider or review not found")
     * )
     */
    public function destroy($serviceProviderId, $reviewId): JsonResponse
    {
        $serviceProvider = ServiceProvider::findOrFail($serviceProviderId);
        $review = ServiceProviderReview::where('service_provider_id', $serviceProviderId)->findOrFail($reviewId);
        $user = Auth::user();

        if ($serviceProvider->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized to delete review',
                'errors' => ['user_id' => 'Only owners or admins can delete reviews'],
            ], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ], 200);
    }
}
