<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use App\Models\Skill;
use App\Models\SkillDomain;
use App\Models\ServiceProviderPicture;
use App\Models\ServiceProviderReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Project;
use App\Models\ProjectPicture;



class ServiceProviderController extends Controller
{    /**
    * @OA\Post(
    *     path="/api/service-providers",
    *     summary="Create a Service Provider",
    *     description="Creates a new service provider for the authenticated user, assigning a domain, skills, and starting price.",
    *     operationId="createServiceProvider",
    *     tags={"Service Providers"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="description", type="string", example="Experienced web developer specializing in e-commerce solutions", nullable=true),
    *             @OA\Property(property="skill_domain_id", type="integer", example=1, description="ID of the skill domain"),
    *             @OA\Property(property="starting_price", type="number", format="float", example=50.00, description="Starting price for services", nullable=true),
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
    *                 @OA\Property(property="starting_price", type="number", format="float", example=50.00, nullable=true),
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
    *     @OA\Response(
    *         response=500,
    *         description="Server error",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Failed to create service provider")
    *         )
    *     ),
    *     security={{"sanctum": {}}}
    * )
    */
   public function store(Request $request)
   {
       $request->validate([
           'description' => 'nullable|string',
           'skill_domain_id' => 'required|exists:skill_domains,id',
           'starting_price' => 'nullable|numeric|min:0',
           'skill_ids' => 'required|array|min:1',
           'skill_ids.*' => 'exists:skills,id',
       ]);

       try {
           $serviceProvider = ServiceProvider::create([
               'user_id' => Auth::id(),
               'skill_domain_id' => $request->skill_domain_id,
               'description' => $request->description,
               'starting_price' => $request->starting_price,
           ]);

           $serviceProvider->skills()->attach($request->skill_ids);

           return response()->json([
               'message' => 'Service provider created successfully',
               'data' => $serviceProvider
           ], 201);
       } catch (QueryException $e) {
           return response()->json([
               'message' => 'Failed to create service provider',
               'error' => 'Database error occurred'
           ], 500);
       }
   }



    /**
 * @OA\Post(
 *     path="/api/service-providers/portfolio/pictures/{id}",
 *     summary="Upload Pictures for a Service Provider",
 *     description="Uploads a list of images for a service provider's portfolio, stores them locally, and saves their paths in the service_provider_pictures table.",
 *     operationId="uploadServiceProviderPictures",
 *     tags={"Portfolio"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The ID of the service provider to associate the pictures with",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="pictures",
 *                     type="array",
 *                     @OA\Items(type="string", format="binary"),
 *                     description="Array of image files to upload"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Pictures uploaded successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Pictures uploaded successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="skill_domain_id", type="integer", example=1),
 *                 @OA\Property(property="description", type="string", example="Experienced web developer", nullable=true),
 *                 @OA\Property(
 *                     property="pictures",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="service_provider_id", type="integer", example=1),
 *                         @OA\Property(property="picture", type="string", example="/storage/service_provider_pictures/image1.jpg")
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
 *             @OA\Property(property="message", type="string", example="Not authorized to upload pictures for this service provider")
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
 *             @OA\Property(property="message", type="string", example="The pictures field is required"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to upload pictures"),
 *             @OA\Property(property="error", type="string", example="Database or storage error")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
    public function uploadPictures(Request $request, $id)
    {
        // Validate the service provider exists and belongs to the authenticated user
        $serviceProvider = ServiceProvider::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        // Validate the request
        $request->validate([
            'pictures' => 'required|array|min:1',
            'pictures.*' => 'file|mimes:jpeg,png,jpg|max:2048', // 2MB max per image
        ]);

        try {
            if ($request->hasFile('pictures') && is_array($request->file('pictures'))) {
                foreach ($request->file('pictures') as $picture) {
                    if ($picture->isValid()) {
                        ServiceProviderPicture::create([
                            'service_provider_id' => $serviceProvider->id,
                            'picture' => $picture->store('service_provider_pictures', 'public')
                        ]);
                    }
                }
            }

            // Log success
            Log::info("Pictures uploaded for service provider: ID {$serviceProvider->id}");

            return response()->json([
                'message' => 'Pictures uploaded successfully',
                'data' => $serviceProvider->load('pictures')
            ], 201);
        } catch (QueryException $e) {
            Log::error('Failed to store picture in database: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to upload pictures',
                'error' => 'Database error occurred'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to store picture in storage: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to upload pictures',
                'error' => 'Storage error occurred'
            ], 500);
        }
    }


        /**
     * @OA\Delete(
     *     path="/api/service-providers/{id}",
     *     summary="Delete a Service Provider",
     *     description="Deletes a service provider and its associated skills and pictures for the authenticated user.",
     *     operationId="deleteServiceProvider",
     *     tags={"Service Providers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the service provider to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service provider deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service provider deleted successfully")
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
     *             @OA\Property(property="message", type="string", example="Not authorized to delete this service provider")
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
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to delete service provider"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function destroy($id)
    {
        try {
            // Find the service provider and ensure it belongs to the authenticated user
            $serviceProvider = ServiceProvider::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Detach skills from pivot table
            $serviceProvider->skills()->detach();

            // Delete the service provider (pictures are cascade-deleted by DB)
            $serviceProvider->delete();

            return response()->json([
                'message' => 'Service provider deleted successfully'
            ], 200);
        } catch (QueryException $e) {
            Log::error('Failed to delete service provider: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete service provider',
                'error' => 'Database error occurred'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error deleting service provider: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete service provider',
                'error' => 'Unexpected error occurred'
            ], 500);
        }
    }

      /**
     * @OA\Get(
     *     path="/api/service-providers/{id}",
     *     summary="Get Service Provider by ID",
     *     description="Retrieves a service provider by ID with its user's full name, skills, pictures, and skill domain.",
     *     operationId="getServiceProviderById",
     *     tags={"Service Providers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the service provider",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service provider retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="skill_domain_id", type="integer", example=4),
     *                 @OA\Property(property="description", type="string", example="Updated web developer profile", nullable=true),
     *                 @OA\Property(property="starting_price", type="string", example="75.00", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-05T09:31:04.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-06T09:28:56.000000Z"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="full_name", type="string", example="Hadj Ben"),
     *                     @OA\Property(property="email", type="string", example="hadj@gmail.com"),
     *                     @OA\Property(property="phone_number", type="string", example="0555123456", nullable=true),
     *                     @OA\Property(property="role", type="string", example="user"),
     *                     @OA\Property(property="picture", type="string", example=null, nullable=true),
     *                     @OA\Property(property="address", type="string", example="123 Algiers St, Algiers, Algeria", nullable=true),
     *                     @OA\Property(property="city", type="string", example=null, nullable=true),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z")
     *                 ),
     *                 @OA\Property(
     *                     property="skill_domain",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=4),
     *                     @OA\Property(property="name", type="string", example="Content Creation"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z")
     *                 ),
     *                 @OA\Property(
     *                     property="skills",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="UI/UX Design"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z"),
     *                         @OA\Property(
     *                             property="pivot",
     *                             type="object",
     *                             @OA\Property(property="service_provider_id", type="integer", example=1),
     *                             @OA\Property(property="skill_id", type="integer", example=1)
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="pictures",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="service_provider_id", type="integer", example=1),
     *                         @OA\Property(property="picture", type="string", example="service_provider_pictures/0qlYO4DTdp7JrD4rCVakwvnNvQEXTcQkCpc1Lz54.jpg"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-05T09:33:07.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-05T09:33:07.000000Z")
     *                     )
     *                 )
     *             )
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
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve service provider"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
public function show($id)
{
    try {
        $serviceProvider = ServiceProvider::with(['skills', 'pictures','skillDomain','user','reviews'])->findOrFail($id);
        return response()->json(['data' => $serviceProvider], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'Service provider not found'], 404);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to retrieve service provider',
            'error' => 'Database error occurred'
        ], 500);
    }
}


/**
 * @OA\Get(
 *     path="/api/service-providers/by-user/{user_id}",
 *     summary="Get Service Provider by User ID",
 *     description="Retrieves a service provider by user ID with its user's full name and picture, skills, pictures, and skill domain.",
 *     operationId="getServiceProviderByUserId",
 *     tags={"Service Providers"},
 *     @OA\Parameter(
 *         name="user_id",
 *         in="path",
 *         description="ID of the user associated with the service provider",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Service provider retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=2),
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="skill_domain_id", type="integer", example=1),
 *                 @OA\Property(property="description", type="string", example="Experienced web developer", nullable=true),
 *                 @OA\Property(property="starting_price", type="string", example="100.00", nullable=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-07T09:33:56.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-07T09:33:56.000000Z"),
 *                 @OA\Property(
 *                     property="user",
 *                     type="object",
 *                     nullable=true,
 *                     @OA\Property(property="full_name", type="string", example="Hadj Ben"),
 *                     @OA\Property(property="picture", type="string", example=null, nullable=true)
 *                 ),
 *                 @OA\Property(
 *                     property="skill_domain",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Design"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z")
 *                 ),
 *                 @OA\Property(
 *                     property="skills",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="name", type="string", example="UI/UX Design"),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-05T09:30:30.000000Z"),
 *                         @OA\Property(
 *                             property="pivot",
 *                             type="object",
 *                             @OA\Property(property="service_provider_id", type="integer", example=2),
 *                             @OA\Property(property="skill_id", type="integer", example=1)
 *                         )
 *                     )
 *                 ),
 *                 @OA\Property(
 *                     property="pictures",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=3),
 *                         @OA\Property(property="service_provider_id", type="integer", example=2),
 *                         @OA\Property(property="picture", type="string", example="service_provider_pictures/jgjpotxIBdZ8kbyMDULtlfdFUlpjnHtucWY426Jg.jpg"),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-07T09:37:21.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-07T09:37:21.000000Z")
 *                     )
 *                 )
 *             )
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
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to retrieve service provider"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */

public function showByUser($user_id)
{
    try {
        $serviceProvider = ServiceProvider::with(['skills', 'pictures', 'skillDomain', 'user' ,'reviews'])
            ->where('user_id', $user_id)
            ->first();

        if (!$serviceProvider) {
            return response()->json([
                'message' => 'Service provider not found',
                'data' => null
            ], 404);
        }

        $serviceProviderData = $serviceProvider->toArray();
        $serviceProviderData['user'] = $serviceProvider->user ? [
            'full_name' => $serviceProvider->user->full_name,
            'picture' => $serviceProvider->user->picture
        ] : null;

        return response()->json(['data' => $serviceProviderData], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to retrieve service provider',
            'error' => 'Database error occurred'
        ], 500);
    }
}


    /**
     * @OA\Get(
     *     path="/api/service-providers",
     *     summary="Get All Service Providers",
     *     description="Retrieves a list of all service providers with their user's full name, skills, pictures, and skill domain.",
     *     operationId="getAllServiceProviders",
     *     tags={"Service Providers"},
     *     @OA\Response(
     *         response=200,
     *         description="Service providers retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="skill_domain_id", type="integer", example=1),
     *                     @OA\Property(property="description", type="string", example="Expert in web development", nullable=true),
     *                     @OA\Property(property="starting_price", type="number", format="float", example=50.00, nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-01T00:00:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-01T00:00:00.000000Z"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="full_name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(
     *                         property="skill_domain",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Web Development")
     *                     ),
     *                     @OA\Property(
     *                         property="skills",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="PHP")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="pictures",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="service_provider_id", type="integer", example=1),
     *                             @OA\Property(property="picture", type="string", example="/storage/pictures/sp1.jpg")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve service providers"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function index()
    {
        try {
            $serviceProviders = ServiceProvider::with(['user', 'skills', 'pictures', 'skillDomain','reviews'])->get()->map(function ($provider) {
                $providerData = $provider->toArray();
                $providerData['user'] = $provider->user ? ['full_name' => $provider->user->full_name,'picture' => $provider->user->picture] : null;
                return $providerData;
            });
            return response()->json(['data' => $serviceProviders], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve service providers',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

        /**
     * @OA\Put(
     *     path="/api/service-providers/{id}",
     *     summary="Update a Service Provider",
     *     description="Updates an existing service provider for the authenticated user, modifying domain, skills, and starting price.",
     *     operationId="updateServiceProvider",
     *     tags={"Service Providers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the service provider to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="description", type="string", example="Experienced web developer specializing in e-commerce solutions", nullable=true),
     *             @OA\Property(property="skill_domain_id", type="integer", example=1, description="ID of the skill domain"),
     *             @OA\Property(property="starting_price", type="number", format="float", example=50.00, description="Starting price for services", nullable=true),
     *             @OA\Property(
     *                 property="skill_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 description="Array of skill IDs to associate with the provider"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service provider updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Service provider updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="skill_domain_id", type="integer", example=1),
     *                 @OA\Property(property="description", type="string", example="Experienced web developer specializing in e-commerce solutions", nullable=true),
     *                 @OA\Property(property="starting_price", type="number", format="float", example=50.00, nullable=true),
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
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You are not authorized to update this service provider")
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
     *             @OA\Property(property="message", type="string", example="The skill_domain_id field is required"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to update service provider"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'description' => 'nullable|string',
            'skill_domain_id' => 'required|exists:skill_domains,id',
            'starting_price' => 'nullable|numeric|min:0',
            'skill_ids' => 'required|array|min:1',
            'skill_ids.*' => 'exists:skills,id',
        ]);

        try {
            $serviceProvider = ServiceProvider::findOrFail($id);

            // Check if the authenticated user owns the service provider
            if ($serviceProvider->user_id !== Auth::id()) {
                return response()->json(['message' => 'You are not authorized to update this service provider'], 403);
            }

            $serviceProvider->update([
                'skill_domain_id' => $request->skill_domain_id,
                'description' => $request->description,
                'starting_price' => $request->starting_price,
            ]);

            // Sync skills (replaces existing skill associations with new ones)
            $serviceProvider->skills()->sync($request->skill_ids);

            return response()->json([
                'message' => 'Service provider updated successfully',
                'data' => $serviceProvider
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Service provider not found'], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Failed to update service provider',
                'error' => 'Database error occurred'
            ], 500);
        }
    }


        /**
     * @OA\Get(
     *     path="/api/service-providers/{id}/portfolio",
     *     summary="Get Service Provider Portfolio",
     *     description="Retrieves a service provider's portfolio by ID, including all projects with their pictures and service provider pictures.",
     *     operationId="getServiceProviderPortfolio",
     *     tags={"Portfolio"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the service provider",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Portfolio retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="projects",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="service_provider_id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="E-commerce Platform"),
     *                         @OA\Property(property="description", type="string", example="A platform for online sales", nullable=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-07T09:33:56.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-07T09:33:56.000000Z"),
     *                         @OA\Property(
     *                             property="pictures",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="project_id", type="integer", example=1),
     *                                 @OA\Property(property="picture", type="string", example="project_pictures/image1.jpg"),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-07T09:37:21.000000Z"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-07T09:37:21.000000Z")
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="service_provider_pictures",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="service_provider_id", type="integer", example=1),
     *                         @OA\Property(property="picture", type="string", example="service_provider_pictures/image1.jpg"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-07T09:37:21.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-07T09:37:21.000000Z")
     *                     )
     *                 )
     *             )
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
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve portfolio"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function getPortfolio($id)
    {
        try {
            $serviceProvider = ServiceProvider::with(['projects.pictures', 'pictures'])->findOrFail($id);
            $portfolio = [
                'projects' => $serviceProvider->projects,
                'service_provider_pictures' => $serviceProvider->pictures
            ];
            return response()->json(['data' => $portfolio], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Service provider not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve portfolio',
                'error' => 'Database error occurred'
            ], 500);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/service-providers/portfolio/pictures/{id}",
     *     summary="Delete a Service Provider Picture",
     *     description="Deletes a picture associated with a service provider's portfolio, if the authenticated user owns the service provider.",
     *     operationId="deleteServiceProviderPicture",
     *     tags={"Portfolio"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the picture to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Picture deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Picture deleted successfully")
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
     *             @OA\Property(property="message", type="string", example="Not authorized to delete this picture")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Picture not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Picture not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to delete picture"),
     *             @OA\Property(property="error", type="string", example="Database or storage error")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function deletePicture($id)
    {
        try {
            $picture = ServiceProviderPicture::findOrFail($id);
            $serviceProvider = ServiceProvider::findOrFail($picture->service_provider_id);

            if ($serviceProvider->user_id !== Auth::id()) {
                return response()->json(['message' => 'Not authorized to delete this picture'], 403);
            }

            Storage::delete($picture->picture);
            $picture->delete();

            return response()->json(['message' => 'Picture deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Picture not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete picture',
                'error' => 'Database or storage error'
            ], 500);
        }
    }





}

?>
