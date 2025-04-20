<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/domains",
     *     summary="Get all domains",
     *     tags={"Domains"},
     *     @OA\Response(
     *         response=200,
     *         description="Domains retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Domains retrieved successfully"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Furniture"
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $domains = Domain::select('id', 'name')->get();
        return response()->json([
            'message' => 'Domains retrieved successfully',
            'data' => $domains
        ], 200);
    }
}