<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SkillDomain;


class SkillsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/skills",
     *     summary="List Skills",
     *     description="Retrieves a paginated list of skills.",
     *     operationId="listSkills",
     *     tags={"Skills"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Skills retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Web Development")
     *             )),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve skills"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $skills = Skill::paginate(20);
            return response()->json($skills, 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve skills: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve skills',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

     /**
     * @OA\Get(
     *     path="/api/skill-domains",
     *     summary="List Skill Domains",
     *     description="Retrieves a paginated list of skill domains.",
     *     operationId="listSkillDomains",
     *     tags={"Skill Domains"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Skill domains retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Programming")
     *             )),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve skill domains"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     )
     * )
     */
    public function indexDomains(Request $request)
    {
        try {
            $skillDomains = SkillDomain::paginate(20);
            return response()->json($skillDomains, 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve skill domains: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve skill domains',
                'error' => 'Database error occurred'
            ], 500);
        }
    }
}

?>