<?php

namespace App\Http\Controllers;

use App\Models\Wilaya;
use App\Models\Commune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WilayaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/wilayas",
     *     summary="Get all Wilayas",
     *     description="Retrieves a list of all Wilayas for populating a dropdown.",
     *     operationId="getWilayas",
     *     tags={"Wilayas"},
     *     @OA\Response(
     *         response=200,
     *         description="Wilayas retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Alger")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve Wilayas"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $wilayas = Wilaya::select('id', 'name')->get();
            return response()->json($wilayas, 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve Wilayas', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to retrieve Wilayas',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/wilayas/{wilaya_id}/communes",
     *     summary="Get Communes by Wilaya ID",
     *     description="Retrieves a list of Communes for a specific Wilaya ID to populate a dropdown.",
     *     operationId="getCommunesByWilaya",
     *     tags={"Wilayas"},
     *     @OA\Parameter(
     *         name="wilaya_id",
     *         in="path",
     *         required=true,
     *         description="ID of the Wilaya",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Communes retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Bab El Oued")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Wilaya not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Wilaya not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve Communes"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     )
     * )
     */
    public function getCommunes($wilaya_id)
    {
        try {
            $wilaya = Wilaya::find($wilaya_id);
            if (!$wilaya) {
                return response()->json(['message' => 'Wilaya not found'], 404);
            }
            $communes = Commune::where('wilaya_id', $wilaya_id)
                ->select('id', 'name')
                ->get();
            return response()->json($communes, 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve Communes', [
                'wilaya_id' => $wilaya_id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Failed to retrieve Communes',
                'error' => 'Database error occurred'
            ], 500);
        }
    }
}
