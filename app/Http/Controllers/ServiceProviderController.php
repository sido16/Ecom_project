<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use App\Models\Skill;
use App\Models\SkillDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceProviderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/service-providers",
     *     summary="Create a Service Provider",
     *     description="Creates a new service provider for the authenticated user, assigning a domain and skills.",
     *     operationId="createServiceProvider",
     *     tags={"Service Providers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="description", type="string", example="Experienced web developer specializing in e-commerce solutions", nullable=true),
     *             @OA\Property(property="skill_domain_id", type="integer", example=1, description="ID of the skill domain"),
     *             @OA\Property(
     *                 property="skill_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 description="Array of skill IDs to associate with the provider"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service provider created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Service provider created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="skill_domain_id", type="integer", example=1),
     *                 @OA\Property(property="description", type="string", example="Experienced web developer specializing in e-commerce solutions", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-11T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-11T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The skill_domain_id field is required"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'description' => 'nullable|string',
            'skill_domain_id' => 'required|exists:skill_domain,id',
            'skill_ids' => 'required|array|min:1',
            'skill_ids.*' => 'exists:skills,id',
        ]);

        $serviceProvider = ServiceProvider::create([
            'user_id' => Auth::id(),
            'skill_domain_id' => $request->skill_domain_id,
            'description' => $request->description,
        ]);

        $serviceProvider->skills()->attach($request->skill_ids);

        return response()->json([
            'message' => 'Service provider created successfully',
            'data' => $serviceProvider
        ], 201);
    }
}

?>