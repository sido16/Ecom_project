<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Get All Categories",
     *     description="Retrieves a list of all categories.",
     *     operationId="getCategories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Categories retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Electronics"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-03T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-03T12:00:00Z")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ], 200);
    }
}
