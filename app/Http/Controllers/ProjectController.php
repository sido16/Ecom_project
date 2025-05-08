<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectPicture;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class ProjectController extends Controller
{
     /**
 * @OA\Post(
 *     path="/api/service_providers/{serviceProvider}/portfolio/projects",
 *     summary="Create a Project with Pictures",
 *     description="Creates a new project for a specified service provider owned by the authenticated user, with optional image uploads stored locally in the project_pictures table.",
 *     operationId="createProjectWithPictures",
 *     tags={"Portfolio"},
 *     @OA\Parameter(
 *         name="serviceProvider",
 *         in="path",
 *         required=true,
 *         description="The ID of the service provider to associate the project with",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     example="E-commerce Platform",
 *                     description="The title of the project"
 *                 ),
 *                 @OA\Property(
 *                     property="description",
 *                     type="string",
 *                     example="A platform for online sales",
 *                     nullable=true,
 *                     description="The description of the project"
 *                 ),
 *                 @OA\Property(
 *                     property="pictures",
 *                     type="array",
 *                     @OA\Items(type="string", format="binary"),
 *                     description="Array of image files to upload"
 *                 ),
 *                 @OA\Property(
 *                     property="service_provider_id",
 *                     type="integer",
 *                     example=1,
 *                     description="Optional service provider ID (overrides URL parameter if provided)"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Project created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Project created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="service_provider_id", type="integer", example=1),
 *                 @OA\Property(property="title", type="string", example="E-commerce Platform"),
 *                 @OA\Property(property="description", type="string", example="A platform for online sales", nullable=true),
 *                 @OA\Property(
 *                     property="pictures",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="project_id", type="integer", example=1),
 *                         @OA\Property(property="picture", type="string", example="/storage/project_pictures/image1.jpg")
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
 *         description="Forbidden",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Not authorized to create projects for this service provider")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Service provider not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Service provider not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The title field is required"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to create project"),
 *             @OA\Property(property="error", type="string", example="Database or storage error")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
    public function store(Request $request, $serviceProvider)
    {
        // Validate the service provider exists and belongs to the authenticated user
        $serviceProvider = ServiceProvider::where('id', $serviceProvider)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Validate the request
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'pictures' => 'sometimes|array|min:1',
            'pictures.*' => 'file|mimes:jpeg,png,jpg|max:2048', // 2MB max per image
        ]);

        try {
            // Create the project
            $project = Project::create([
                'service_provider_id' => $serviceProvider->id,
                'title' => $request->title,
                'description' => $request->description,
            ]);

            // Handle picture uploads
            if ($request->hasFile('pictures') && is_array($request->file('pictures'))) {
                foreach ($request->file('pictures') as $picture) {
                    if ($picture->isValid()) {
                        ProjectPicture::create([
                            'project_id' => $project->id,
                            'picture' => $picture->store('project_pictures', 'public')
                        ]);
                    }
                }
            }

            // Log success
            Log::info("Project created: ID {$project->id}, Service Provider ID: {$serviceProvider->id}");

            return response()->json([
                'message' => 'Project created successfully',
                'data' => $project->load('pictures')
            ], 201);
        } catch (QueryException $e) {
            Log::error('Failed to create project or store pictures: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create project',
                'error' => 'Database error occurred'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to store project pictures in storage: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create project',
                'error' => 'Storage error occurred'
            ], 500);
        }
    }



    /**
 * @OA\Delete(
 *     path="/api/service_providers/portfolio/projects/{id}",
 *     summary="Delete a Project",
 *     description="Deletes a project and its associated pictures for the authenticated user, if they own the associated service provider.",
 *     operationId="deleteProject",
 *     tags={"Portfolio"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The ID of the project to delete",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Project deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Project deleted successfully")
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
 *         description="Forbidden",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Not authorized to delete this project")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Project not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Project not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to delete project"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
    public function destroy($id)
    {
        try {
            $project = Project::where('id', $id)
                ->whereHas('serviceProvider', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->firstOrFail();
            $project->delete();

            return response()->json([
                'message' => 'Project deleted successfully'
            ], 200);
        } catch (QueryException $e) {
            Log::error('Failed to delete project: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete project',
                'error' => 'Database error occurred'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error deleting project: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete project',
                'error' => 'Unexpected error occurred'
            ], 500);
        }
    }

        /**
 * @OA\Put(
 *     path="/api/projects/{id}",
 *     summary="Update a Project",
 *     description="Updates an existing project and its pictures for a service provider, restricted to the authenticated user who owns the service provider.",
 *     operationId="updateProject",
 *     tags={"Projects"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the project to update",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="title", type="string", example="Updated Website Project", description="Title of the project", maxLength=100),
 *             @OA\Property(property="description", type="string", example="Updated description for a 5-page website", nullable=true, description="Description of the project"),
 *             @OA\Property(
 *                 property="pictures",
 *                 type="array",
 *                 nullable=true,
 *                 description="Array of picture files to upload",
 *                 @OA\Items(type="string", format="binary")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Project updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Project updated successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="service_provider_id", type="integer", example=1),
 *                 @OA\Property(property="title", type="string", example="Updated Website Project"),
 *                 @OA\Property(property="description", type="string", example="Updated description for a 5-page website", nullable=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-08T10:05:00.000000Z"),
 *                 @OA\Property(
 *                     property="pictures",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="project_id", type="integer", example=1),
 *                         @OA\Property(property="picture", type="string", example="project_pictures/image1.jpg"),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z")
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
 *         description="Forbidden",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Not authorized to update this project")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Project not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Project not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The title field is required"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to update project"),
 *             @OA\Property(property="error", type="string", example="Database or storage error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function update(Request $request, $id)
{
    try {
        $project = Project::with('pictures')->findOrFail($id);
        $serviceProvider = ServiceProvider::where('id', $project->service_provider_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'pictures' => 'sometimes|array|min:1',
            'pictures.*' => 'file|mimes:jpeg,png,jpg|max:2048',
        ]);

        return DB::transaction(function () use ($request, $project) {
            $project->update([
                'title' => $request->title,
                'description' => $request->description,
            ]);

            if ($request->hasFile('pictures') && is_array($request->file('pictures'))) {
                foreach ($project->pictures as $existingPicture) {
                    Storage::disk('public')->delete($existingPicture->picture);
                    $existingPicture->delete();
                }

                foreach ($request->file('pictures') as $picture) {
                    if ($picture->isValid()) {
                        ProjectPicture::create([
                            'project_id' => $project->id,
                            'picture' => $picture->store('project_pictures', 'public')
                        ]);
                    }
                }
            }

            Log::info("Project updated: ID {$project->id}, Service Provider ID: {$project->service_provider_id}");

            return response()->json([
                'message' => 'Project updated successfully',
                'data' => $project->load('pictures')
            ], 200);
        });
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Project not found'], 404);
    } catch (QueryException $e) {
        Log::error('Failed to update project or store pictures: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to update project',
            'error' => 'Database error occurred'
        ], 500);
    } catch (\Exception $e) {
        Log::error('Failed to update project pictures in storage: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to update project',
            'error' => 'Storage error occurred'
        ], 500);
    }
}



}

?>