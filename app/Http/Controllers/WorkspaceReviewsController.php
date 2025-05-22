<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;


/**
 * @OA\Tag(
 *     name="Workspace Reviews",
 *     description="API Endpoints for managing workspace reviews"
 * )
 */
class WorkspaceReviewsController extends Controller
{
    /**
     * Display a listing of reviews for a workspace.
     *
     * @param int $workspaceId
     * @return JsonResponse
     */

      /**
     * Get a list of reviews for a specific workspace.
     *
     * @OA\Get(
     *     path="/api/workspaces/{workspaceId}/reviews",
     *     operationId="getWorkspaceReviews",
     *     tags={"Workspace Reviews"},
     *     summary="List reviews for a workspace",
     *     description="Retrieve all reviews for a given workspace including average and total rating stats.",
     *     @OA\Parameter(
     *         name="workspaceId",
     *         in="path",
     *         description="Workspace ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="reviews", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total_rating", type="integer", example=25),
     *                 @OA\Property(property="average_rating", type="number", format="float", example=4.2),
     *                 @OA\Property(property="review_count", type="integer", example=6),
     *             ),
     *             @OA\Property(property="message", type="string", example="Reviews retrieved successfully")
     *         )
     *     )
     * )
     */
     public function index($workspaceId): JsonResponse
    {
        $workspace = Workspace::findOrFail($workspaceId);

        $reviews = WorkspaceReview::with(['user', 'replyUser'])
            ->where('workspace_id', $workspaceId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate total, average, and count
        $stats = WorkspaceReview::where('workspace_id', $workspaceId)
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
     * Store a new review for a workspace.
     *
     * @param Request $request
     * @param int $workspaceId
     * @return JsonResponse
     */

     /**
     * Submit a new review for a workspace.
     *
     * @OA\Post(
     *     path="/api/workspaces/{workspaceId}/reviews",
     *     operationId="submitWorkspaceReview",
     *     tags={"Workspace Reviews"},
     *     summary="Submit a review",
     *     description="Allows an authenticated user to submit a review for a workspace, excluding the owner.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="workspaceId",
     *         in="path",
     *         description="Workspace ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating", "comment"},
     *             @OA\Property(property="rating", type="integer", example=5, minimum=1, maximum=5),
     *             @OA\Property(property="comment", type="string", example="This place was amazing!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Review successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", example={"id":12, "rating":5, "comment":"Great!", "workspace_id":1}),
     *             @OA\Property(property="message", type="string", example="Review submitted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Cannot review own workspace")
     * )
     */
    public function storeReview(Request $request, $workspaceId): JsonResponse
    {
        \Log::info('Store Review Request:', [
            'workspaceId' => $workspaceId,
            'input' => $request->all(),
            'user' => Auth::user()
        ]);

        try {
            $workspace = Workspace::findOrFail($workspaceId);
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'errors' => ['user' => 'No authenticated user found'],
                ], 401);
            }

            // Prevent owner from reviewing their own workspace
            if ($workspace->user_id === $user->id) {
                return response()->json([
                    'message' => 'You cannot review your own workspace',
                    'errors' => ['workspace_id' => 'Owners cannot review their own workspace'],
                ], 403);
            }

            // Validate request
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

            // Create review
            $review = WorkspaceReview::create([
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return response()->json([
                'data' => $review->load('user'),
                'message' => 'Review submitted successfully',
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Store Review Error:', [
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
     * Store a reply to a review.
     *
     * @param Request $request
     * @param int $workspaceId
     * @param int $reviewId
     * @return JsonResponse
     */

     /**
 * Submit a reply to a specific review (workspace owner only).
 *
 * @OA\Post(
 *     path="/api/workspaces/{workspaceId}/reviews/{reviewId}/reply",
 *     operationId="replyToWorkspaceReview",  
 *     tags={"Workspace Reviews"},
 *     summary="Reply to a review",
 *     description="Allows the owner of the workspace to reply to a specific review.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="workspaceId",
 *         in="path",
 *         description="Workspace ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="reviewId",
 *         in="path",
 *         description="Review ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"comment"},
 *             @OA\Property(property="comment", type="string", example="Thank you for your feedback!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Reply submitted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object", example={"id":15, "reply":"Thanks!"}),
 *             @OA\Property(property="message", type="string", example="Reply submitted successfully")
 *         )
 *     ),
 *     @OA\Response(response=403, description="Only workspace owner can reply"),
 *     @OA\Response(response=422, description="Reply already exists or validation error")
 * )
 */

    public function storeReply(Request $request, $workspaceId, $reviewId): JsonResponse
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $review = WorkspaceReview::where('workspace_id', $workspaceId)
            ->findOrFail($reviewId);
        $user = Auth::user();

        // Only workspace owner can reply
        if ($workspace->user_id !== $user->id) {
            return response()->json([
                'message' => 'Only the workspace owner can reply to reviews',
                'errors' => ['user_id' => 'Unauthorized to reply'],
            ], 403);
        }

        // Prevent multiple replies
        if ($review->reply !== null) {
            return response()->json([
                'message' => 'A reply already exists for this review',
                'errors' => ['reply' => 'Only one reply is allowed per review'],
            ], 422);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|min:10|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update review with reply
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
     * Remove a review.
     *
     * @param int $workspaceId
     * @param int $reviewId
     * @return JsonResponse
     */

     /**
     * Delete a review (workspace owner or admin only).
     *
     * @OA\Delete(
     *     path="/api/workspaces/{workspaceId}/reviews/{reviewId}",
     *     operationId="deleteWorkspaceReview",
     *     tags={"Workspace Reviews"},
     *     summary="Delete a review",
     *     description="Allows a workspace owner or admin to delete a specific review.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="workspaceId",
     *         in="path",
     *         description="Workspace ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="reviewId",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized to delete review")
     * )
     */
    public function destroy($workspaceId, $reviewId): JsonResponse
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $review = WorkspaceReview::where('workspace_id', $workspaceId)->findOrFail($reviewId);
        $user = Auth::user();

        // Only owner or admin can delete
        if ($workspace->user_id !== $user->id && !$user->hasRole('admin')) {
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
