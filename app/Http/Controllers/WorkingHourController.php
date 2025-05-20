<?php

namespace App\Http\Controllers;

use App\Models\WorkingHour;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkingHourController extends Controller
{

/**
 * @OA\Schema(
 *     schema="WorkingHour",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="workspace_id", type="integer", example=1),
 *     @OA\Property(property="day", type="integer", example=1),
 *     @OA\Property(property="time_from", type="string", format="time", example="09:00:00"),
 *     @OA\Property(property="time_to", type="string", format="time", example="17:00:00"),
 *     @OA\Property(property="is_open", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Post(
 *     path="/workspaces/{workspace_id}/working-hours",
 *     summary="Create working hours for a workspace",
 *     description="Creates multiple working hour entries for a given workspace. Each entry includes day, opening and closing times, and whether the workspace is open.",
 *     tags={"Working Hours"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         required=true,
 *         description="ID of the workspace",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 required={"day", "time_from", "time_to", "is_open"},
 *                 @OA\Property(
 *                     property="day",
 *                     type="string",
 *                     enum={"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"},
 *                     example="Monday"
 *                 ),
 *                 @OA\Property(
 *                     property="time_from",
 *                     type="string",
 *                     format="time",
 *                     example="09:00"
 *                 ),
 *                 @OA\Property(
 *                     property="time_to",
 *                     type="string",
 *                     format="time",
 *                     example="17:00"
 *                 ),
 *                 @OA\Property(
 *                     property="is_open",
 *                     type="boolean",
 *                     example=true
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Working hours created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Working hours created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="workspace_id", type="integer", example=1),
 *                     @OA\Property(property="day", type="integer", example=1),
 *                     @OA\Property(property="time_from", type="string", format="time", example="09:00:00"),
 *                     @OA\Property(property="time_to", type="string", format="time", example="17:00:00"),
 *                     @OA\Property(property="is_open", type="boolean", example=true),
 *                     @OA\Property(property="created_at", type="string", format="date-time"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Validation error"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace not found or unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found or unauthorized")
 *         )
 *     )
 * )
 */

    public function createWorkingHours(Request $request, $workspace_id)
{
    try {
        $request->validate([
            '*.day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            '*.time_from' => 'required|date_format:H:i',
            '*.time_to' => 'required|date_format:H:i|after:*.time_from',
            '*.is_open' => 'required|boolean',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
    }

    $workspace = Workspace::where('id', $workspace_id)
        ->where('user_id', Auth::id())
        ->first();

    if (!$workspace) {
        return response()->json(['message' => 'Workspace not found or unauthorized'], 404);
    }

    return DB::transaction(function () use ($request, $workspace_id) {
        $dayMap = [
            'Monday' => 3,
            'Tuesday' => 4,
            'Wednesday' => 5,
            'Thursday' => 6,
            'Friday' => 7,
            'Saturday' => 1,
            'Sunday' => 2,
        ];

        $workingHours = [];

        foreach ($request->all() as $hour) {
            $workingHours[] = WorkingHour::create([
                'workspace_id' => $workspace_id,
                'day' => $dayMap[$hour['day']],
                'time_from' => $hour['time_from'] . ':00',
                'time_to' => $hour['time_to'] . ':00',
                'is_open' => $hour['is_open'],
            ]);
        }

        return response()->json([
            'message' => 'Working hours created successfully',
            'data' => $workingHours,
        ], 201);
    });
}

/**
 * @OA\Put(
 *     path="/api/workspaces/{workspace_id}/working-hours",
 *     summary="Update multiple working hours by ID",
 *     tags={"Working Hours"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the workspace",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="day", type="string", example="Monday"),
 *                 @OA\Property(property="time_from", type="string", format="time", example="08:00"),
 *                 @OA\Property(property="time_to", type="string", format="time", example="17:00"),
 *                 @OA\Property(property="is_open", type="boolean", example=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Working hours updated successfully"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace or working hour not found"
 *     )
 * )
 */
public function updateWorkingHours(Request $request, $workspace_id)
{
    try {
        $request->validate([
            '*.id' => 'required|integer|exists:working_hours,id',
            '*.day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            '*.time_from' => 'required|date_format:H:i',
            '*.time_to' => 'required|date_format:H:i|after:*.time_from',
            '*.is_open' => 'required|boolean',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
    }

    $workspace = Workspace::where('id', $workspace_id)
        ->where('user_id', Auth::id())
        ->first();

    if (!$workspace) {
        return response()->json(['message' => 'Workspace not found or unauthorized'], 404);
    }

    $dayMap = [
        'Monday' => 3,
        'Tuesday' => 4,
        'Wednesday' => 5,
        'Thursday' => 6,
        'Friday' => 7,
        'Saturday' => 1,
        'Sunday' => 2,
    ];

    return DB::transaction(function () use ($request, $workspace_id, $dayMap) {
        $updatedHours = [];

        foreach ($request->all() as $hour) {
            $workingHour = WorkingHour::where('id', $hour['id'])
                ->where('workspace_id', $workspace_id)
                ->first();

            if (!$workingHour) {
                throw new \Exception("Working hour ID {$hour['id']} not found for this workspace.");
            }

            $workingHour->update([
                'day' => $dayMap[$hour['day']],
                'time_from' => $hour['time_from'] . ':00',
                'time_to' => $hour['time_to'] . ':00',
                'is_open' => $hour['is_open'],
            ]);

            $updatedHours[] = $workingHour;
        }

        return response()->json([
            'message' => 'Working hours updated successfully',
            'data' => $updatedHours,
        ]);
    });
}


}