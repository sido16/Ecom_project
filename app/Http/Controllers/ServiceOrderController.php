<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceOrder;
use App\Models\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServiceOrderController extends Controller
{
        /**
     * @OA\Post(
     *     path="/api/service-orders",
     *     summary="Create a Service Order",
     *     description="Creates a new service order for a service provider, placed by the authenticated user.",
     *     operationId="createServiceOrder",
     *     tags={"Service Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="service_provider_id", type="integer", example=1, description="ID of the service provider"),
     *             @OA\Property(property="skill_id", type="integer", example=1, nullable=true, description="ID of the skill (optional)"),
     *             @OA\Property(property="title", type="string", example="Website Development", description="Title of the order"),
     *             @OA\Property(property="description", type="string", example="Develop a 5-page website", nullable=true, description="Description of the order"),
     *             @OA\Property(property="deadline", type="string", format="date", example="2025-06-01", description="Deadline for the order"),
     *             @OA\Property(property="total_amount", type="number", format="float", example=500.00, nullable=true, description="Total amount for the order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service order created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Service order created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="service_provider_id", type="integer", example=1),
     *                 @OA\Property(property="skill_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="title", type="string", example="Website Development"),
     *                 @OA\Property(property="description", type="string", example="Develop a 5-page website", nullable=true),
     *                 @OA\Property(property="deadline", type="string", format="date", example="2025-06-01"),
     *                 @OA\Property(property="total_amount", type="string", example="500.00", nullable=true),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="full_name", type="string", example="Jane Doe")
     *                 ),
     *                 @OA\Property(
     *                     property="service_provider",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="full_name", type="string", example="Hadj Ben")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="skill",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="UI/UX Design")
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
     *             @OA\Property(property="message", type="string", example="Cannot order from yourself")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service provider or skill not found")
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
     *             @OA\Property(property="message", type="string", example="Failed to create service order"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'service_provider_id' => 'required|integer|exists:service_providers,id',
                'skill_id' => 'nullable|integer|exists:skills,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'deadline' => 'required|date|after:today',
                'total_amount' => 'nullable|numeric|min:0',
            ]);

            $serviceProvider = ServiceProvider::findOrFail($validated['service_provider_id']);
            if ($serviceProvider->user_id === Auth::id()) {
                return response()->json(['message' => 'Cannot order from yourself'], 403);
            }

            return DB::transaction(function () use ($validated, $serviceProvider) {
                $order = ServiceOrder::create([
                    'user_id' => Auth::id(),
                    'service_provider_id' => $validated['service_provider_id'],
                    'skill_id' => $validated['skill_id'],
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'deadline' => $validated['deadline'],
                    'total_amount' => $validated['total_amount'],
                    'status' => 'pending',
                ]);

                $order->load(['user' => function ($query) {
                    $query->select('id', 'full_name');
                }, 'serviceProvider.user' => function ($query) {
                    $query->select('id', 'full_name');
                }, 'skill' => function ($query) {
                    $query->select('id', 'name');
                }]);

                return response()->json([
                    'message' => 'Service order created successfully',
                    'data' => $order
                ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Service provider or skill not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create service order',
                'error' => 'Database error occurred'
            ], 500);
        }
    }
/**
 * @OA\Patch(
 *     path="/api/service-orders/{id}/status",
 *     summary="Update Service Order Status",
 *     description="Updates the status of a service order, restricted to the service provider.",
 *     operationId="updateServiceOrderStatus",
 *     tags={"Service Orders"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the service order",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="status",
 *                 type="string",
 *                 enum={"pending", "confirmed", "completed", "cancelled"},
 *                 example="confirmed",
 *                 description="The new status of the order"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Service order status updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Service order status updated successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=2),
 *                 @OA\Property(property="service_provider_id", type="integer", example=1),
 *                 @OA\Property(property="skill_id", type="integer", example=1, nullable=true),
 *                 @OA\Property(property="title", type="string", example="Website Development"),
 *                 @OA\Property(property="description", type="string", example="Develop a 5-page website", nullable=true),
 *                 @OA\Property(property="deadline", type="string", format="date", example="2025-06-01"),
 *                 @OA\Property(property="total_amount", type="string", example="500.00", nullable=true),
 *                 @OA\Property(
 *                     property="status",
 *                     type="string",
 *                     enum={"pending", "confirmed", "completed", "cancelled"},
 *                     example="confirmed"
 *                 ),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z")
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
 *             @OA\Property(property="message", type="string", example="Not authorized to update this order")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Service order not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Service order not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The status field is required"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to update service order"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function updateStatus(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $order = ServiceOrder::findOrFail($id);

        if ($order->service_provider_id !== Auth::user()->serviceProvider?->id) {
            return response()->json(['message' => 'Not authorized to update this order'], 403);
        }

        $order->status = $validated['status'];
        $order->save();

        return response()->json([
            'message' => 'Service order status updated successfully',
            'data' => $order
        ], 200);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Service order not found'], 404);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to update service order',
            'error' => 'Database error occurred'
        ], 500);
    }
}

/**
 * @OA\Get(
 *     path="/api/service-orders/{id}",
 *     summary="Get Service Order by ID",
 *     description="Retrieves a service order by its ID, accessible to the customer or service provider.",
 *     operationId="getServiceOrderById",
 *     tags={"Service Orders"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the service order",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Service order retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="user_id", type="integer", example=2),
 *                 @OA\Property(property="service_provider_id", type="integer", example=1),
 *                 @OA\Property(property="skill_id", type="integer", example=1, nullable=true),
 *                 @OA\Property(property="title", type="string", example="Website Development"),
 *                 @OA\Property(property="description", type="string", example="Develop a 5-page website", nullable=true),
 *                 @OA\Property(property="deadline", type="string", format="date", example="2025-06-01"),
 *                 @OA\Property(property="total_amount", type="string", example="500.00", nullable=true),
 *                 @OA\Property(property="status", type="string", example="pending"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
 *                 @OA\Property(
 *                     property="user",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=2),
 *                     @OA\Property(property="full_name", type="string", example="Jane Doe")
 *                 ),
 *                 @OA\Property(
 *                     property="service_provider",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="user_id", type="integer", example=1),
 *                     @OA\Property(
 *                         property="user",
 *                         type="object",
 *                         @OA\Property(property="full_name", type="string", example="Hadj Ben")
 *                     )
 *                 ),
 *                 @OA\Property(
 *                     property="skill",
 *                     type="object",
 *                     nullable=true,
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="UI/UX Design")
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
 *             @OA\Property(property="message", type="string", example="Not authorized to view this order")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Service order not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Service order not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to retrieve service order"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function show($id)
{
    try {
        $order = ServiceOrder::with([
            'user' => function ($query) {
                $query->select('id', 'full_name');
            },
            'serviceProvider.user' => function ($query) {
                $query->select('id', 'full_name');
            },
            'skill' => function ($query) {
                $query->select('id', 'name');
            }
        ])->findOrFail($id);

        if ($order->user_id !== Auth::id() && $order->service_provider_id !== Auth::user()->serviceProvider?->id) {
            return response()->json(['message' => 'Not authorized to view this order'], 403);
        }

        return response()->json(['data' => $order], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Service order not found'], 404);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to retrieve service order',
            'error' => 'Database error occurred'
        ], 500);
    }
}

/**
 * @OA\Get(
 *     path="/api/service-orders/user/{user_id}",
 *     summary="Get Service Orders by User ID",
 *     description="Retrieves all service orders placed by a specific user.",
 *     operationId="getServiceOrdersByUserId",
 *     tags={"Service Orders"},
 *     @OA\Parameter(
 *         name="user_id",
 *         in="path",
 *         required=true,
 *         description="ID of the user",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Service orders retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="user_id", type="integer", example=2),
 *                     @OA\Property(property="service_provider_id", type="integer", example=1),
 *                     @OA\Property(property="skill_id", type="integer", example=1, nullable=true),
 *                     @OA\Property(property="title", type="string", example="Website Development"),
 *                     @OA\Property(property="description", type="string", example="Develop a 5-page website", nullable=true),
 *                     @OA\Property(property="deadline", type="string", format="date", example="2025-06-01"),
 *                     @OA\Property(property="total_amount", type="string", example="500.00", nullable=true),
 *                     @OA\Property(property="status", type="string", example="pending"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
 *                     @OA\Property(
 *                         property="user",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=2),
 *                         @OA\Property(property="full_name", type="string", example="Jane Doe")
 *                     ),
 *                     @OA\Property(
 *                         property="service_provider",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="user_id", type="integer", example=1),
 *                         @OA\Property(
 *                             property="user",
 *                             type="object",
 *                             @OA\Property(property="full_name", type="string", example="Hadj Ben")
 *                         )
 *                     ),
 *                     @OA\Property(
 *                         property="skill",
 *                         type="object",
 *                         nullable=true,
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="name", type="string", example="UI/UX Design")
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
 *             @OA\Property(property="message", type="string", example="Not authorized to view these orders")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to retrieve service orders"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function getByUser($user_id)
{
    try {
        if ($user_id != Auth::id()) {
            return response()->json(['message' => 'Not authorized to view these orders'], 403);
        }

        $orders = ServiceOrder::with([
            'user' => function ($query) {
                $query->select('id', 'full_name');
            },
            'serviceProvider.user' => function ($query) {
                $query->select('id', 'full_name');
            },
            'skill' => function ($query) {
                $query->select('id', 'name');
            }
        ])->where('user_id', $user_id)->get();

        return response()->json(['data' => $orders], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to retrieve service orders',
            'error' => 'Database error occurred'
        ], 500);
    }
}

/**
 * @OA\Get(
 *     path="/api/service-orders/service-provider/{service_provider_id}",
 *     summary="Get Service Orders by Service Provider ID",
 *     description="Retrieves all service orders assigned to a specific service provider, including full user information.",
 *     operationId="getServiceOrdersByServiceProviderId",
 *     tags={"Service Orders"},
 *     @OA\Parameter(
 *         name="service_provider_id",
 *         in="path",
 *         required=true,
 *         description="ID of the service provider",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Service orders retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="user_id", type="integer", example=2),
 *                     @OA\Property(property="service_provider_id", type="integer", example=1),
 *                     @OA\Property(property="skill_id", type="integer", example=1, nullable=true),
 *                     @OA\Property(property="title", type="string", example="Website Development"),
 *                     @OA\Property(property="description", type="string", example="Develop a 5-page website", nullable=true),
 *                     @OA\Property(property="deadline", type="string", format="date", example="2025-06-01"),
 *                     @OA\Property(property="total_amount", type="string", example="500.00", nullable=true),
 *                     @OA\Property(property="status", type="string", example="pending"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-08T10:00:00.000000Z"),
 *                     @OA\Property(
 *                         property="user",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=2),
 *                         @OA\Property(property="full_name", type="string", example="Jane Doe"),
 *                         @OA\Property(property="email", type="string", example="jane.doe@example.com"),
 *                         @OA\Property(property="phone_number", type="string", example="1234567890", nullable=true),
 *                         @OA\Property(property="role", type="string", example="user"),
 *                         @OA\Property(property="picture", type="string", example=null, nullable=true),
 *                         @OA\Property(property="address", type="string", example="123 Algiers St, Algiers, Algeria", nullable=true),
 *                         @OA\Property(property="city", type="string", example=null, nullable=true),
 *                         @OA\Property(property="email_verified_at", type="string", format="date-time", example="2025-05-12T12:48:11.000000Z", nullable=true),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-01T10:00:00.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-01T10:00:00.000000Z")
 *                     ),
 *                     @OA\Property(
 *                         property="service_provider",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="user_id", type="integer", example=1),
 *                         @OA\Property(
 *                             property="user",
 *                             type="object",
 *                             @OA\Property(property="id", type="integer", example=1),
 *                             @OA\Property(property="full_name", type="string", example="Hadj Ben"),
 *                             @OA\Property(property="email", type="string", example="hadj.ben@example.com"),
 *                             @OA\Property(property="phone_number", type="string", example="0987654321", nullable=true),
 *                             @OA\Property(property="role", type="string", example="service_provider"),
 *                             @OA\Property(property="picture", type="string", example=null, nullable=true),
 *                             @OA\Property(property="address", type="string", example="456 Algiers St, Algiers, Algeria", nullable=true),
 *                             @OA\Property(property="city", type="string", example=null, nullable=true),
 *                             @OA\Property(property="email_verified_at", type="string", format="date-time", example="2025-05-12T12:48:11.000000Z", nullable=true),
 *                             @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-01T10:00:00.000000Z"),
 *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-01T10:00:00.000000Z")
 *                         )
 *                     ),
 *                     @OA\Property(
 *                         property="skill",
 *                         type="object",
 *                         nullable=true,
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="name", type="string", example="UI/UX Design")
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
 *             @OA\Property(property="message", type="string", example="Not authorized to view these orders")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to retrieve service orders"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
public function getByServiceProvider($service_provider_id)
{
    try {
        if ($service_provider_id != Auth::user()->serviceProvider?->id) {
            return response()->json(['message' => 'Not authorized to view these orders'], 403);
        }

        $orders = ServiceOrder::with([
            'user' ,
            'serviceProvider.user' => function ($query) {
                $query->select('id', 'full_name',);
            },
            'skill' => function ($query) {
                $query->select('id', 'name');
            }
        ])->where('service_provider_id', $service_provider_id)->get();

        return response()->json(['data' => $orders], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to retrieve service orders',
            'error' => 'Database error occurred'
        ], 500);
    }
}


}
