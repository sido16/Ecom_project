<?php

namespace App\Http\Controllers;

use App\Models\Studio;
use App\Models\Workspace;
use App\Models\WorkspaceImage;
use App\Models\Coworking;
use App\Models\WorkingHour;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;




class WorkspaceController extends Controller
{

  /**
 * @OA\Post(
 *     path="/api/workspaces/studio/create",
 *     summary="Create a Studio Workspace",
 *     description="Creates a new studio workspace with associated details, services, and a single main picture for the authenticated user.",
 *     operationId="createStudioWorkspace",
 *     tags={"Workspaces"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="business_name", type="string", example="PhotoSnap Studio", description="Business name", maxLength=255),
 *                 @OA\Property(property="phone_number", type="string", example="1234567890", description="Phone number", maxLength=50),
 *                 @OA\Property(property="email", type="string", example="contact@photosnap.com", description="Email", maxLength=255),
 *                 @OA\Property(property="location", type="string", example="Downtown", description="General location", nullable=true),
 *                 @OA\Property(property="address", type="string", example="123 Main St", description="Street address", maxLength=100),
 *                 @OA\Property(property="description", type="string", example="Professional photography studio", description="Description", nullable=true),
 *                 @OA\Property(property="opening_hours", type="string", example="9AM-5PM", description="Opening hours", maxLength=255, nullable=true),
 *                 @OA\Property(property="picture", type="string", format="binary", description="Main workspace picture", nullable=true),
 *                 @OA\Property(property="price_per_hour", type="number", format="float", example=50.00, description="Hourly rental price"),
 *                 @OA\Property(property="price_per_day", type="number", format="float", example=200.00, description="Daily rental price"),
 *                 @OA\Property(property="studio_service_ids", type="array", description="Array of studio service IDs", @OA\Items(type="integer", example=1))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Studio created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Studio created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="business_name", type="string", example="PhotoSnap Studio"),
 *                 @OA\Property(property="type", type="string", example="studio"),
 *                 @OA\Property(property="studio", type="object", @OA\Property(property="id", type="integer", example=1))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The business_name field is required"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to create studio"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
    public function createStudio(Request $request)
    {
        try {
            $request->validate([
                'business_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:50|unique:workspaces,phone_number',
                'email' => 'required|email|max:255|unique:workspaces,email',
                'location' => 'nullable|string',
                'address' => 'required|string|max:100',
                'description' => 'nullable|string',
                'opening_hours' => 'nullable|string|max:255',
                'picture' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
                'price_per_hour' => 'required|numeric|min:0',
                'price_per_day' => 'required|numeric|min:0',
                'studio_service_ids' => 'sometimes|array|min:1',
                'studio_service_ids.*' => 'exists:studio_services,id',
                'images' => 'sometimes|array|min:1',
                'images.*' => 'file|mimes:jpeg,png,jpg|max:2048',
            ]);

            return DB::transaction(function () use ($request) {
                $workspace = Workspace::create([
                    'user_id' => Auth::id(),
                    'business_name' => $request->business_name,
                    'type' => 'studio',
                    'phone_number' => $request->phone_number,
                    'email' => $request->email,
                    'location' => $request->location,
                    'address' => $request->address,
                    'description' => $request->description,
                    'opening_hours' => $request->opening_hours,
                    'picture' => $request->hasFile('picture') ? $request->file('picture')->store('workspace_pictures', 'public') : null,
                    'is_active' => true,
                ]);

                $studio = Studio::create([
                    'workspace_id' => $workspace->id,
                    'price_per_hour' => $request->price_per_hour,
                    'price_per_day' => $request->price_per_day,
                ]);

                if ($request->has('studio_service_ids')) {
                    $studio->services()->sync($request->studio_service_ids);
                }

                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        WorkspaceImage::create([
                            'workspace_id' => $workspace->id,
                            'image_url' => $image->store('workspace_images', 'public'),
                        ]);
                    }
                }

                Log::info("Studio created: ID {$workspace->id}, User ID: " . Auth::id());

                return response()->json([
                    'message' => 'Studio created successfully',
                    'data' => $workspace->load(['studio', 'studio.services', 'images']),
                ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Failed to create studio: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create studio',
                'error' => 'Database error occurred',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to store studio images: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create studio',
                'error' => 'Storage error occurred',
            ], 500);
        }
    }


      /**
 * @OA\Post(
 *     path="/api/workspaces/{workspace_id}/studio/images",
 *     summary="Insert Images for a Studio Workspace",
 *     description="Uploads and associates multiple additional images with an existing studio workspace for the authenticated user.",
 *     operationId="insertStudioWorkspaceImages",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the studio workspace to add images to",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="images",
 *                     type="array",
 *                     description="Array of image files to upload",
 *                     @OA\Items(type="string", format="binary")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Images inserted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Images inserted successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="workspace_id", type="integer", example=1),
 *                     @OA\Property(property="image_url", type="string", example="workspace_images/image1.jpg"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized action",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You are not authorized to add images to this workspace")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The images field is required"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to insert images"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */

    public function insertStudioPictures(Request $request, $workspace_id)
    {
        try {
            $request->validate([
                'images' => 'required|array|min:1',
                'images.*' => 'file|mimes:jpeg,png,jpg|max:2048',
            ]);

            $workspace = Workspace::where('id', $workspace_id)
                ->where('type', 'studio')
                ->first();

            if (!$workspace) {
                return response()->json([
                    'message' => 'Workspace not found',
                ], 404);
            }

            if ($workspace->user_id !== Auth::id()) {
                return response()->json([
                    'message' => 'You are not authorized to add pictures to this workspace',
                ], 403);
            }

            return DB::transaction(function () use ($request, $workspace) {
                $insertedImages = [];

                foreach ($request->file('images') as $image) {
                    $imagePath = $image->store('workspace_images', 'public');
                    $workspaceImage = WorkspaceImage::create([
                        'workspace_id' => $workspace->id,
                        'image_url' => $imagePath,
                    ]);
                    $insertedImages[] = $workspaceImage;
                }

                Log::info("Pictures inserted for workspace ID {$workspace->id}, User ID: " . Auth::id());

                return response()->json([
                    'message' => 'Pictures inserted successfully',
                    'data' => $insertedImages,
                ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Failed to insert pictures: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to insert pictures',
                'error' => 'Database error occurred',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to store pictures: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to insert pictures',
                'error' => 'Storage error occurred',
            ], 500);
        }
    }

/**
 * @OA\Post(
 *     path="/api/workspaces/coworking/create",
 *     summary="Create a Coworking Workspace",
 *     description="Creates a new coworking workspace with associated details and a single main picture for the authenticated user.",
 *     operationId="createCoworkingWorkspace",
 *     tags={"Workspaces"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="business_name", type="string", example="WorkHub Coworking", description="Business name", maxLength=255),
 *                 @OA\Property(property="phone_number", type="string", example="1234567890", description="Phone number", maxLength=50),
 *                 @OA\Property(property="email", type="string", example="info@workhub.com", description="Email", maxLength=255),
 *                 @OA\Property(property="location", type="string", example="Downtown", description="General location", nullable=true),
 *                 @OA\Property(property="address", type="string", example="456 Elm St", description="Street address", maxLength=100),
 *                 @OA\Property(property="description", type="string", example="Modern coworking space with Wi-Fi", description="Description", nullable=true),
 *                 @OA\Property(property="opening_hours", type="string", example="8AM-6PM", description="Opening hours", maxLength=255, nullable=true),
 *                 @OA\Property(property="picture", type="string", format="binary", description="Main workspace picture", nullable=true),
 *                 @OA\Property(property="price_per_day", type="number", format="float", example=25.00, description="Daily rental price"),
 *                 @OA\Property(property="price_per_month", type="number", format="float", example=400.00, description="Monthly rental price"),
 *                 @OA\Property(property="seating_capacity", type="integer", example=50, description="Number of available seats"),
 *                 @OA\Property(property="meeting_rooms", type="integer", example=3, description="Number of meeting rooms")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Coworking created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Coworking created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="business_name", type="string", example="WorkHub Coworking"),
 *                 @OA\Property(property="type", type="string", example="coworking"),
 *                 @OA\Property(property="coworking", type="object", @OA\Property(property="id", type="integer", example=1))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The business_name field is required"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to create coworking"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */

    public function createCoworking(Request $request)
    {
        try {
            $request->validate([
                'business_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:50|unique:workspaces,phone_number',
                'email' => 'required|email|max:255|unique:workspaces,email',
                'location' => 'nullable|string',
                'address' => 'required|string|max:100',
                'description' => 'nullable|string',
                'opening_hours' => 'nullable|string|max:255',
                'picture' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
                'price_per_day' => 'required|numeric|min:0',
                'price_per_month' => 'required|numeric|min:0',
                'seating_capacity' => 'required|integer|min:1',
                'meeting_rooms' => 'required|integer|min:0',
            ]);

            return DB::transaction(function () use ($request) {
                $workspace = Workspace::create([
                    'user_id' => Auth::id(),
                    'business_name' => $request->business_name,
                    'type' => 'coworking',
                    'phone_number' => $request->phone_number,
                    'email' => $request->email,
                    'location' => $request->location,
                    'address' => $request->address,
                    'description' => $request->description,
                    'opening_hours' => $request->opening_hours,
                    'picture' => $request->hasFile('picture') ? $request->file('picture')->store('workspace_pictures', 'public') : null,
                    'is_active' => true,
                ]);

                Coworking::create([
                    'workspace_id' => $workspace->id,
                    'price_per_day' => $request->price_per_day,
                    'price_per_month' => $request->price_per_month,
                    'seating_capacity' => $request->seating_capacity,
                    'meeting_rooms' => $request->meeting_rooms,
                ]);

                Log::info("Coworking created: ID {$workspace->id}, User ID: " . Auth::id());

                return response()->json([
                    'message' => 'Coworking created successfully',
                    'data' => $workspace->load('coworking'),
                ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Failed to create coworking: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create coworking',
                'error' => 'Database error occurred',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to store coworking picture: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create coworking',
                'error' => 'Storage error occurred',
            ], 500);
        }
    }


        /**
 * @OA\Post(
 *     path="/api/workspaces/{workspace_id}/coworking/images",
 *     summary="Insert Images for a Coworking Workspace",
 *     description="Uploads and associates multiple additional images with an existing coworking workspace for the authenticated user.",
 *     operationId="insertCoworkingWorkspaceImages",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the coworking workspace to add images to",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="images",
 *                     type="array",
 *                     description="Array of additional image files to upload",
 *                     @OA\Items(type="string", format="binary")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Images inserted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Images inserted successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="workspace_id", type="integer", example=1),
 *                     @OA\Property(property="image_url", type="string", example="workspace_images/image1.jpg"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized action",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You are not authorized to add images to this workspace")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The images field is required"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to insert images"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
    public function insertCoworkingPictures(Request $request, $workspace_id)
    {
        try {
            $request->validate([
                'pictures' => 'required|array|min:1',
                'pictures.*' => 'file|mimes:jpeg,png,jpg|max:2048',
            ]);

            $workspace = Workspace::where('id', $workspace_id)
                ->whereIn('type', ['studio', 'coworking'])
                ->first();

            if (!$workspace) {
                return response()->json([
                    'message' => 'Workspace not found',
                ], 404);
            }

            if ($workspace->user_id !== Auth::id()) {
                return response()->json([
                    'message' => 'You are not authorized to add pictures to this workspace',
                ], 403);
            }

            return DB::transaction(function () use ($request, $workspace) {
                $insertedImages = [];

                foreach ($request->file('pictures') as $picture) {
                    $imagePath = $picture->store('workspace_images', 'public');
                    $workspaceImage = WorkspaceImage::create([
                        'workspace_id' => $workspace->id,
                        'image_url' => $imagePath,
                    ]);
                    $insertedImages[] = $workspaceImage;
                }

                Log::info("Pictures inserted for workspace ID {$workspace->id}, User ID: " . Auth::id());

                return response()->json([
                    'message' => 'Pictures inserted successfully',
                    'data' => $insertedImages,
                ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Failed to insert pictures: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to insert pictures',
                'error' => 'Database error occurred',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to store pictures: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to insert pictures',
                'error' => 'Storage error occurred',
            ], 500);
        }
    }



        /**
 * @OA\Get(
 *     path="/api/workspaces/type/{type}",
 *     summary="Get Workspaces by Type",
 *     description="Retrieves all workspaces of the specified type (studio or coworking) with their associated details, excluding images, for the authenticated user or publicly.",
 *     operationId="getWorkspacesByType",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="type",
 *         in="path",
 *         description="Type of workspace to retrieve (studio or coworking)",
 *         required=true,
 *         @OA\Schema(type="string", enum={"studio", "coworking"}, example="studio")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Workspaces retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspaces retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="user_id", type="integer", example=1),
 *                     @OA\Property(property="business_name", type="string", example="PhotoSnap Studio"),
 *                     @OA\Property(property="type", type="string", example="studio"),
 *                     @OA\Property(property="phone_number", type="string", example="1234567890"),
 *                     @OA\Property(property="email", type="string", example="contact@photosnap.com"),
 *                     @OA\Property(property="location", type="string", example="Downtown", nullable=true),
 *                     @OA\Property(property="address", type="string", example="123 Main St"),
 *                     @OA\Property(property="description", type="string", example="Professional photography studio", nullable=true),
 *                     @OA\Property(property="opening_hours", type="string", example="9AM-5PM", nullable=true),
 *                     @OA\Property(property="picture", type="string", example="workspace_pictures/studio.jpg", nullable=true),
 *                     @OA\Property(property="is_active", type="boolean", example=true),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                     @OA\Property(
 *                         property="studio",
 *                         type="object",
 *                         nullable=true,
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="workspace_id", type="integer", example=1),
 *                         @OA\Property(property="price_per_hour", type="number", format="float", example=50.00),
 *                         @OA\Property(property="price_per_day", type="number", format="float", example=200.00),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                         @OA\Property(
 *                             property="services",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="id", type="integer", example=1),
 *                                 @OA\Property(property="service", type="string", example="Lighting Equipment")
 *                             )
 *                         )
 *                     ),
 *                     @OA\Property(
 *                         property="coworking",
 *                         type="object",
 *                         nullable=true,
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="workspace_id", type="integer", example=1),
 *                         @OA\Property(property="price_per_day", type="number", format="float", example=25.00),
 *                         @OA\Property(property="price_per_month", type="number", format="float", example=400.00),
 *                         @OA\Property(property="seating_capacity", type="integer", example=50),
 *                         @OA\Property(property="meeting_rooms", type="integer", example=3),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z")
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid workspace type",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Invalid workspace type. Use 'studio' or 'coworking'.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to retrieve workspaces"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}, {}}
 * )
 */
public function getWorkspacesByType(Request $request, $type)
{
    try {
        // Validate the type parameter
        if (!in_array($type, ['studio', 'coworking'])) {
            return response()->json([
                'message' => "Invalid workspace type. Use 'studio' or 'coworking'.",
            ], 400);
        }

        // Query workspaces by type, including studio or coworking details
        $query = Workspace::where('type', $type)
            ->where('is_active', true);

        // If authenticated, optionally filter by user
        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        }

        $workspaces = $query->with([
            'studio','coworking' ])->get();
        Log::info("Retrieved workspaces of type {$type}", ['count' => $workspaces->count()]);

        return response()->json([
            'message' => 'Workspaces retrieved successfully',
            'data' => $workspaces,
        ], 200);
    } catch (\Exception $e) {
        Log::error("Failed to retrieve workspaces of type {$type}: " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to retrieve workspaces',
            'error' => 'Server error occurred',
        ], 500);
    }
}

/**
 * @OA\Get(
 *     path="/api/workspaces/{workspace_id}",
 *     summary="Get Workspace by ID",
 *     description="Retrieves a single workspace by its ID, including all associated details and images, for the authenticated user or publicly.",
 *     operationId="getWorkspaceById",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the workspace to retrieve",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Workspace retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="business_name", type="string", example="PhotoSnap Studio"),
 *                 @OA\Property(property="type", type="string", example="studio"),
 *                 @OA\Property(property="phone_number", type="string", example="1234567890"),
 *                 @OA\Property(property="email", type="string", example="contact@photosnap.com"),
 *                 @OA\Property(property="location", type="string", example="Downtown", nullable=true),
 *                 @OA\Property(property="address", type="string", example="123 Main St"),
 *                 @OA\Property(property="description", type="string", example="Professional photography studio", nullable=true),
 *                 @OA\Property(property="opening_hours", type="string", example="9AM-5PM", nullable=true),
 *                 @OA\Property(property="picture", type="string", example="workspace_pictures/studio.jpg", nullable=true),
 *                 @OA\Property(property="is_active", type="boolean", example=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                 @OA\Property(
 *                     property="studio",
 *                     type="object",
 *                     nullable=true,
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="workspace_id", type="integer", example=1),
 *                     @OA\Property(property="price_per_hour", type="number", format="float", example=50.00),
 *                     @OA\Property(property="price_per_day", type="number", format="float", example=200.00),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                     @OA\Property(
 *                         property="services",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(property="id", type="integer", example=1),
 *                             @OA\Property(property="service", type="string", example="Lighting Equipment")
 *                         )
 *                     )
 *                 ),
 *                 @OA\Property(
 *                     property="coworking",
 *                     type="object",
 *                     nullable=true,
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="workspace_id", type="integer", example=1),
 *                     @OA\Property(property="price_per_day", type="number", format="float", example=25.00),
 *                     @OA\Property(property="price_per_month", type="number", format="float", example=400.00),
 *                     @OA\Property(property="seating_capacity", type="integer", example=50),
 *                     @OA\Property(property="meeting_rooms", type="integer", example=3),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z")
 *                 ),
 *                 @OA\Property(
 *                     property="images",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="workspace_id", type="integer", example=1),
 *                         @OA\Property(property="image_url", type="string", example="workspace_images/image1.jpg"),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z")
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to retrieve workspace"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}, {}}
 * )
 */
public function getWorkspaceById($workspace_id)
{
    try {
        // Query workspace by ID, including all details and images
        $workspace = Workspace::where('id', $workspace_id)
            ->with([
                'studio' => function ($query) {
                    $query->with('services:id,service');
                },
                'coworking',
                'images',
                'workingHours'
            ])
            ->first();

        if (!$workspace) {
            return response()->json([
                'message' => 'Workspace not found',
            ], 404);
        }

        // If authenticated, optionally check ownership
        if (Auth::check() && $workspace->user_id !== Auth::id()) {
            // Optionally restrict access; here we allow public access
            // return response()->json(['message' => 'Unauthorized'], 403);
        }

        Log::info("Retrieved workspace ID {$workspace_id}");

        return response()->json([
            'message' => 'Workspace retrieved successfully',
            'data' => $workspace,
        ], 200);
    } catch (\Exception $e) {
        Log::error("Failed to retrieve workspace ID {$workspace_id}: " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to retrieve workspace',
            'error' => 'Server error occurred',
        ], 500);
    }
}

   /**
 * @OA\Get(
 *     path="/api/workspaces/user",
 *     summary="Get Workspaces by Authenticated User",
 *     description="Retrieves all active workspaces (studio or coworking) for the currently authenticated user, including studio and coworking details (excluding services and images).",
 *     operationId="getWorkspacesByUser",
 *     tags={"Workspaces"},
 *     @OA\Response(
 *         response=200,
 *         description="Workspaces retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspaces retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="user_id", type="integer", example=1),
 *                     @OA\Property(property="business_name", type="string", example="PhotoSnap Studio"),
 *                     @OA\Property(property="type", type="string", example="studio"),
 *                     @OA\Property(property="phone_number", type="string", example="1234567890"),
 *                     @OA\Property(property="email", type="string", example="contact@photosnap.com"),
 *                     @OA\Property(property="location", type="string", example="Downtown", nullable=true),
 *                     @OA\Property(property="address", type="string", example="123 Main St"),
 *                     @OA\Property(property="description", type="string", example="Professional photography studio", nullable=true),
 *                     @OA\Property(property="opening_hours", type="string", example="9AM-5PM", nullable=true),
 *                     @OA\Property(property="picture", type="string", example="workspace_pictures/studio.jpg", nullable=true),
 *                     @OA\Property(property="is_active", type="boolean", example=true),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-13T23:02:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-13T23:02:00.000000Z"),
 *                     @OA\Property(
 *                         property="studio",
 *                         type="object",
 *                         nullable=true,
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="workspace_id", type="integer", example=1),
 *                         @OA\Property(property="price_per_hour", type="number", format="float", example=50.00),
 *                         @OA\Property(property="price_per_day", type="number", format="float", example=200.00),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-13T23:02:00.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-13T23:02:00.000000Z")
 *                     ),
 *                     @OA\Property(
 *                         property="coworking",
 *                         type="object",
 *                         nullable=true,
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="workspace_id", type="integer", example=1),
 *                         @OA\Property(property="price_per_day", type="number", format="float", example=25.00),
 *                         @OA\Property(property="price_per_month", type="number", format="float", example=400.00),
 *                         @OA\Property(property="seating_capacity", type="integer", example=50),
 *                         @OA\Property(property="meeting_rooms", type="integer", example=3),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-13T23:02:00.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-13T23:02:00.000000Z")
 *                     )
 *                 )
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
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to retrieve workspaces"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function getWorkspacesByUser()
{
    try {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Query workspaces for the authenticated user, including studio and coworking details
        $workspaces = Workspace::where('user_id', Auth::id())
            ->with(['studio', 'coworking', 'images', 'workingHours'])
            ->get();

        Log::info("Retrieved workspaces for user ID " . Auth::id(), ['count' => $workspaces->count()]);

        return response()->json([
            'message' => 'Workspaces retrieved successfully',
            'data' => $workspaces,
        ], 200);
    } catch (\Exception $e) {
        Log::error("Failed to retrieve workspaces for user ID " . Auth::id() . ": " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to retrieve workspaces',
            'error' => 'Server error occurred',
        ], 500);
    }
}


/**
 * @OA\Delete(
 *     path="/api/workspaces/studio/{workspace_id}",
 *     summary="Delete a Studio Workspace",
 *     description="Deletes a studio workspace and its associated data (studio details and images) for the authenticated user.",
 *     operationId="deleteStudio",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the studio workspace to delete",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Studio workspace deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Studio workspace deleted successfully")
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
 *         response=403,
 *         description="Unauthorized action",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You are not authorized to delete this workspace")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace not found or not a studio",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found or not a studio")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to delete studio workspace"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function deleteStudio(Request $request, $workspace_id)
{
    try {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Find the workspace
        $workspace = Workspace::where('id', $workspace_id)
            ->where('user_id', Auth::id())
            ->where('type', 'studio')
            ->where('is_active', true)
            ->first();

        if (!$workspace) {
            return response()->json([
                'message' => 'Workspace not found or not a studio',
            ], 404);
        }

        // Perform deletion in a transaction
        DB::transaction(function () use ($workspace) {
            // Delete associated studio (cascades to offered_services via DB constraints)
            $workspace->studio()->delete();
            // Delete associated images
            $workspace->images()->delete();
            // Soft delete the workspace
            $workspace->delete();
        });

        Log::info("Deleted studio workspace ID {$workspace_id} for user ID " . Auth::id());

        return response()->json([
            'message' => 'Studio workspace deleted successfully',
        ], 200);
    } catch (\Exception $e) {
        Log::error("Failed to delete studio workspace ID {$workspace_id} for user ID " . Auth::id() . ": " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to delete studio workspace',
            'error' => 'Server error occurred',
        ], 500);
    }
}

/**
 * @OA\Delete(
 *     path="/api/workspaces/coworking/{workspace_id}",
 *     summary="Delete a Coworking Workspace",
 *     description="Deletes a coworking workspace and its associated data (coworking details and images) for the authenticated user.",
 *     operationId="deleteCoworking",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the coworking workspace to delete",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Coworking workspace deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Coworking workspace deleted successfully")
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
 *         response=403,
 *         description="Unauthorized action",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You are not authorized to delete this workspace")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace not found or not a coworking",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found or not a coworking")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to delete coworking workspace"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function deleteCoworking(Request $request, $workspace_id)
{
    try {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Find the workspace
        $workspace = Workspace::where('id', $workspace_id)
            ->where('user_id', Auth::id())
            ->where('type', 'coworking')
            ->where('is_active', true)
            ->first();

        if (!$workspace) {
            return response()->json([
                'message' => 'Workspace not found or not a coworking',
            ], 404);
        }

        // Perform deletion in a transaction
        DB::transaction(function () use ($workspace) {
            // Delete associated coworking
            $workspace->coworking()->delete();
            // Delete associated images
            $workspace->images()->delete();
            // Soft delete the workspace
            $workspace->delete();
        });

        Log::info("Deleted coworking workspace ID {$workspace_id} for user ID " . Auth::id());

        return response()->json([
            'message' => 'Coworking workspace deleted successfully',
        ], 200);
    } catch (\Exception $e) {
        Log::error("Failed to delete coworking workspace ID {$workspace_id} for user ID " . Auth::id() . ": " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to delete coworking workspace',
            'error' => 'Server error occurred',
        ], 500);
    }
}


/**
 * @OA\post(
 *     path="/api/workspaces/coworking/{workspace_id}",
 *     summary="Update a Coworking Workspace",
 *     description="Updates an existing coworking workspace and its associated details for the authenticated user.",
 *     operationId="updateCoworking",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the coworking workspace to update",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="business_name", type="string", example="WorkHub Coworking", maxLength=255),
 *             @OA\Property(property="phone_number", type="string", example="1234567890", maxLength=50),
 *             @OA\Property(property="email", type="string", example="info@workhub.com", maxLength=255),
 *             @OA\Property(property="location", type="string", example="Downtown", nullable=true),
 *             @OA\Property(property="address", type="string", example="456 Elm St", maxLength=100),
 *             @OA\Property(property="description", type="string", example="Modern coworking space", nullable=true),
 *             @OA\Property(property="opening_hours", type="string", example="8AM-6PM", maxLength=255, nullable=true),
 *             @OA\Property(property="picture", type="string", format="binary", nullable=true),
 *             @OA\Property(property="price_per_day", type="number", format="float", example=25.00, minimum=0),
 *             @OA\Property(property="price_per_month", type="number", format="float", example=400.00, minimum=0),
 *             @OA\Property(property="seating_capacity", type="integer", example=50, minimum=1),
 *             @OA\Property(property="meeting_rooms", type="integer", example=3, minimum=0)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Coworking workspace updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Coworking workspace updated successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="business_name", type="string", example="WorkHub Coworking"),
 *                 @OA\Property(property="type", type="string", example="coworking"),
 *                 @OA\Property(property="phone_number", type="string", example="1234567890"),
 *                 @OA\Property(property="email", type="string", example="info@workhub.com"),
 *                 @OA\Property(property="location", type="string", example="Downtown", nullable=true),
 *                 @OA\Property(property="address", type="string", example="456 Elm St"),
 *                 @OA\Property(property="description", type="string", example="Modern coworking space", nullable=true),
 *                 @OA\Property(property="opening_hours", type="string", example="8AM-6PM", nullable=true),
 *                 @OA\Property(property="picture", type="string", example="workspace_pictures/coworking.jpg", nullable=true),
 *                 @OA\Property(property="is_active", type="boolean", example=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-14T23:17:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-14T23:17:00.000000Z"),
 *                 @OA\Property(
 *                     property="coworking",
 *                     type="object",
 *                     nullable=true,
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="workspace_id", type="integer", example=1),
 *                     @OA\Property(property="price_per_day", type="number", format="float", example=25.00),
 *                     @OA\Property(property="price_per_month", type="number", format="float", example=400.00),
 *                     @OA\Property(property="seating_capacity", type="integer", example=50),
 *                     @OA\Property(property="meeting_rooms", type="integer", example=3),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-14T23:17:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-14T23:17:00.000000Z")
 *                 )
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
 *         response=403,
 *         description="Unauthorized action",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You are not authorized to update this workspace")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace not found or not a coworking",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found or not a coworking")
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
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to update coworking workspace"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function updateCoworking(Request $request, $workspace_id)
{
    try {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Find the workspace
        $workspace = Workspace::where('id', $workspace_id)
            ->where('user_id', Auth::id())
            ->where('type', 'coworking')
            ->where('is_active', true)
            ->first();

        if (!$workspace) {
            return response()->json([
                'message' => 'Workspace not found or not a coworking',
            ], 404);
        }

        // Validate request data
        $request->validate([
            'business_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:50|unique:workspaces,phone_number,' . $workspace->id,
            'email' => 'required|email|max:255|unique:workspaces,email,' . $workspace->id,
            'location' => 'nullable|string',
            'address' => 'required|string|max:100',
            'description' => 'nullable|string',
            'opening_hours' => 'nullable|string|max:255',
            'picture' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'price_per_day' => 'required|numeric|min:0',
            'price_per_month' => 'required|numeric|min:0',
            'seating_capacity' => 'required|integer|min:1',
            'meeting_rooms' => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($request, $workspace) {
            // Handle picture upload
            $picturePath = $workspace->picture;
            if ($request->hasFile('picture')) {
                // Delete old picture if exists
                if ($picturePath) {
                    Storage::disk('public')->delete($picturePath);
                }
                $picturePath = $request->file('picture')->store('workspace_pictures', 'public');
            }

            // Update workspace
            $workspace->update([
                'business_name' => $request->business_name,
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'location' => $request->location,
                'address' => $request->address,
                'description' => $request->description,
                'opening_hours' => $request->opening_hours,
                'picture' => $picturePath,
            ]);

            // Update coworking details
            $workspace->coworking()->update([
                'price_per_day' => $request->price_per_day,
                'price_per_month' => $request->price_per_month,
                'seating_capacity' => $request->seating_capacity,
                'meeting_rooms' => $request->meeting_rooms,
            ]);

            Log::info("Coworking workspace updated: ID {$workspace->id}, User ID: " . Auth::id());

            return response()->json([
                'message' => 'Coworking workspace updated successfully',
                'data' => $workspace->load('coworking'),
            ], 200);
        });
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $e->errors(),
        ], 422);
    } catch (QueryException $e) {
        Log::error("Failed to update coworking workspace ID {$workspace_id}: " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to update coworking workspace',
            'error' => 'Database error occurred',
        ], 500);
    } catch (\Exception $e) {
        Log::error("Failed to update coworking workspace ID {$workspace_id}: " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to update coworking workspace',
            'error' => 'Storage error occurred',
        ], 500);
    }
}


/**
 * @OA\post(
 *     path="/api/workspaces/studio/{workspace_id}",
 *     summary="Update a Studio Workspace",
 *     description="Updates an existing studio workspace, its associated details, services, and images for the authenticated user.",
 *     operationId="updateStudio",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the studio workspace to update",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="business_name", type="string", example="PhotoSnap Studio", maxLength=255),
 *             @OA\Property(property="phone_number", type="string", example="1234567890", maxLength=50),
 *             @OA\Property(property="email", type="string", example="contact@photosnap.com", maxLength=255),
 *             @OA\Property(property="location", type="string", example="Downtown", nullable=true),
 *             @OA\Property(property="address", type="string", example="123 Main St", maxLength=100),
 *             @OA\Property(property="description", type="string", example="Professional photography studio", nullable=true),
 *             @OA\Property(property="opening_hours", type="string", example="9AM-5PM", maxLength=255, nullable=true),
 *             @OA\Property(property="picture", type="string", format="binary", nullable=true),
 *             @OA\Property(property="price_per_hour", type="number", format="float", example=50.00, minimum=0),
 *             @OA\Property(property="price_per_day", type="number", format="float", example=200.00, minimum=0),
 *             @OA\Property(
 *                 property="studio_service_ids",
 *                 type="array",
 *                 nullable=true,
 *                 @OA\Items(type="integer", example=1)
 *             ),
 *             @OA\Property(
 *                 property="images",
 *                 type="array",
 *                 nullable=true,
 *                 @OA\Items(type="string", format="binary")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Studio workspace updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Studio workspace updated successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="business_name", type="string", example="PhotoSnap Studio"),
 *                 @OA\Property(property="type", type="string", example="studio"),
 *                 @OA\Property(property="phone_number", type="string", example="1234567890"),
 *                 @OA\Property(property="email", type="string", example="contact@photosnap.com"),
 *                 @OA\Property(property="location", type="string", example="Downtown", nullable=true),
 *                 @OA\Property(property="address", type="string", example="123 Main St"),
 *                 @OA\Property(property="description", type="string", example="Professional photography studio", nullable=true),
 *                 @OA\Property(property="opening_hours", type="string", example="9AM-5PM", nullable=true),
 *                 @OA\Property(property="picture", type="string", example="workspace_pictures/studio.jpg", nullable=true),
 *                 @OA\Property(property="is_active", type="boolean", example=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-14T23:29:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-14T23:29:00.000000Z"),
 *                 @OA\Property(
 *                     property="studio",
 *                     type="object",
 *                     nullable=true,
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="workspace_id", type="integer", example=1),
 *                     @OA\Property(property="price_per_hour", type="number", format="float", example=50.00),
 *                     @OA\Property(property="price_per_day", type="number", format="float", example=200.00),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-14T23:29:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-14T23:29:00.000000Z")
 *                 ),
 *                 @OA\Property(
 *                     property="images",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="workspace_id", type="integer", example=1),
 *                         @OA\Property(property="image_url", type="string", example="workspace_images/image1.jpg"),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-14T23:29:00.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-14T23:29:00.000000Z")
 *                     )
 *                 )
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
 *         response=403,
 *         description="Unauthorized action",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You are not authorized to update this workspace")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace not found or not a studio",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found or not a studio")
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
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to update studio workspace"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function updateStudio(Request $request, $workspace_id)
{
    try {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Find the workspace
        $workspace = Workspace::where('id', $workspace_id)
            ->where('user_id', Auth::id())
            ->where('type', 'studio')
            ->first();

        if (!$workspace) {
            return response()->json([
                'message' => 'Workspace not found or not a studio',
            ], 404);
        }

        // Validate request data
        $request->validate([
            'business_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:50|unique:workspaces,phone_number,' . $workspace->id,
            'email' => 'required|email|max:255|unique:workspaces,email,' . $workspace->id,
            'location' => 'nullable|string',
            'address' => 'required|string|max:100',
            'description' => 'nullable|string',
            'opening_hours' => 'nullable|string|max:255',
            'picture' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'price_per_hour' => 'required|numeric|min:0',
            'price_per_day' => 'required|numeric|min:0',
            'studio_service_ids' => 'sometimes|array|min:1',
            'studio_service_ids.*' => 'exists:studio_services,id',
        ]);

        return DB::transaction(function () use ($request, $workspace) {
            // Handle picture upload
            $picturePath = $workspace->picture;
            if ($request->hasFile('picture')) {
                // Delete old picture if exists
                if ($picturePath) {
                    Storage::disk('public')->delete($picturePath);
                }
                $picturePath = $request->file('picture')->store('workspace_pictures', 'public');
            }

            // Update workspace
            $workspace->update([
                'business_name' => $request->business_name,
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'location' => $request->location,
                'address' => $request->address,
                'description' => $request->description,
                'opening_hours' => $request->opening_hours,
                'picture' => $picturePath,
                'is_active' => !$workspace->is_active,
            ]);

            // Update studio details
            $workspace->studio()->update([
                'price_per_hour' => $request->price_per_hour,
                'price_per_day' => $request->price_per_day,
            ]);

            // Update studio services if provided
            if ($request->has('studio_service_ids')) {
                $workspace->studio->services()->sync($request->studio_service_ids);
            }

            // Update images if provided (replace existing images)
            if ($request->hasFile('images')) {
                // Delete existing images
                foreach ($workspace->images as $image) {
                    Storage::disk('public')->delete($image->image_url);
                    $image->delete();
                }
                // Store new images
                foreach ($request->file('images') as $image) {
                    WorkspaceImage::create([
                        'workspace_id' => $workspace->id,
                        'image_url' => $image->store('workspace_images', 'public'),
                    ]);
                }
            }

            Log::info("Studio workspace updated: ID {$workspace->id}, User ID: " . Auth::id());

            return response()->json([
                'message' => 'Studio workspace updated successfully',
                'data' => $workspace->load(['studio', 'images']),
            ], 200);
        });
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $e->errors(),
        ], 422);
    } catch (QueryException $e) {
        Log::error("Failed to update studio workspace ID {$workspace_id}: " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to update studio workspace',
            'error' => 'Database error occurred',
        ], 500);
    } catch (\Exception $e) {
        Log::error("Failed to update studio workspace ID {$workspace_id}: " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to update studio workspace',
            'error' => 'Storage error occurred',
        ], 500);
    }
}

/**
 * @OA\Delete(
 *     path="/workspaces/{workspace_id}/studio/images/{image_id}",
 *     summary="Delete Studio Workspace Image",
 *     description="Deletes an image associated with a studio workspace owned by the authenticated user.",
 *     operationId="deleteStudioImage",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the workspace",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="image_id",
 *         in="path",
 *         description="ID of the image to delete",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Image deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Image deleted successfully")
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
 *         response=404,
 *         description="Workspace not found or not a studio",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found or not a studio")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to delete image"),
 *             @OA\Property(property="error", type="string", example="Server error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */



public function deleteStudioImage($workspace_id, $image_id)
{
    try {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }
                // Find the workspace
        $workspace = Workspace::where('id', $workspace_id)
            ->where('user_id', Auth::id())
            ->where('type', 'studio')
            ->first();

        if (!$workspace) {
            return response()->json([
                'message' => 'Workspace not found or not a studio',
            ], 404);
        }
               // Find the image
        $image = WorkspaceImage::where('workspace_id', $workspace->id)
            ->where('id', $image_id)
            ->first();

        if (!$image) {
            return response()->json([
                'message' => 'Image not found',
            ], 404);
        }

        if ($image->image_url) {
            Storage::disk('public')->delete($image->image_url);
        } // Delete the image record
        $image->delete();

        Log::info("Deleted image ID {$image_id} from workspace ID {$workspace->id}, User ID: " . Auth::id());

        return response()->json([
            'message' => 'Image deleted successfully',
        ], 200);
    } catch (\Exception $e) {
        Log::error("Failed to delete image ID {$image_id} from workspace ID {$workspace_id}: " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to delete image',
            'error' => 'Server error occurred',
        ], 500);
    }
}

/**
 * @OA\Delete(
 *     path="/workspaces/{workspace_id}/coworking/images/{image_id}",
 *     summary="Delete Coworking Workspace Image",
 *     description="Deletes an image associated with a coworking workspace owned by the authenticated user.",
 *     operationId="deleteCoworkingImage",
 *     tags={"Workspaces"},
 *     @OA\Parameter(
 *         name="workspace_id",
 *         in="path",
 *         description="ID of the workspace",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="image_id",
 *         in="path",
 *         description="ID of the image to delete",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Image deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Image deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized access",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You are not authorized to delete images from this workspace")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Workspace or image not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Workspace not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to delete image"),
 *             @OA\Property(property="error", type="string", example="Server error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function deleteCoworkingImage($workspace_id, $image_id)
{
    try {
        // Find the workspace
        $workspace = Workspace::where('id', $workspace_id)
            ->where('type', 'coworking')
            ->first();

        if (!$workspace) {
            return response()->json([
                'message' => 'Workspace not found',
            ], 404);
        }

        // Check if user owns the workspace
        if ($workspace->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You are not authorized to delete images from this workspace',
            ], 403);
        }

        // Find the image
        $image = WorkspaceImage::where('id', $image_id)
            ->where('workspace_id', $workspace_id)
            ->first();

        if (!$image) {
            return response()->json([
                'message' => 'Image not found',
            ], 404);
        }

        // Delete the image file from storage
        if ($image->image_url) {
            Storage::disk('public')->delete($image->image_url);
        }

        // Delete the image record from database
        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully',
        ], 200);
    } catch (\Exception $e) {
        Log::error("Failed to delete image from workspace ID {$workspace_id}: " . $e->getMessage());
        return response()->json([
            'message' => 'Failed to delete image',
            'error' => 'Server error occurred',
        ], 500);
    }
}
}
