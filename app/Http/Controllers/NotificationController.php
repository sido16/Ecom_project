<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Get All Notifications",
     *     description="Retrieves all notifications for the authenticated user with pagination and filtering options.",
     *     operationId="getNotifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of notifications per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by notification type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"order_status", "order_validated", "all"}, default="all")
     *     ),
     *     @OA\Parameter(
     *         name="read_status",
     *         in="query",
     *         description="Filter by read status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"read", "unread", "all"}, default="all")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notifications retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="last_page", type="integer", example=2),
     *                 @OA\Property(
     *                     property="notifications",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="9d2f8a5c-4b3e-4a1d-8c9f-1e2d3c4b5a6f"),
     *                         @OA\Property(property="type", type="string", example="App\\Notifications\\OrderStatusUpdatedNotification"),
     *                         @OA\Property(property="notifiable_type", type="string", example="App\\Models\\User"),
     *                         @OA\Property(property="notifiable_id", type="integer", example=1),
     *                         @OA\Property(
     *                             property="data",
     *                             type="object",
     *                             @OA\Property(property="order_id", type="integer", example=123),
     *                             @OA\Property(property="message", type="string", example="Order #123 status updated to: delivered"),
     *                             @OA\Property(property="status_message", type="string", example="Your order has been delivered"),
     *                             @OA\Property(property="old_status", type="string", example="processing"),
     *                             @OA\Property(property="new_status", type="string", example="delivered"),
     *                             @OA\Property(property="supplier_name", type="string", example="Tech Store")
     *                         ),
     *                         @OA\Property(property="read_at", type="string", format="date-time", nullable=true, example=null),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                         @OA\Property(property="notification_type", type="string", example="order_status"),
     *                         @OA\Property(property="is_read", type="boolean", example=false)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="total_notifications", type="integer", example=25),
     *                 @OA\Property(property="unread_count", type="integer", example=5),
     *                 @OA\Property(property="order_status_count", type="integer", example=15),
     *                 @OA\Property(property="order_validated_count", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve notifications"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'type' => 'in:order_status,order_validated,all',
                'read_status' => 'in:read,unread,all'
            ]);

            $user = Auth::user();
            $type = $request->get('type', 'all');
            $readStatus = $request->get('read_status', 'all');

            $query = $user->notifications();

            // Filter by notification type
            if ($type !== 'all') {
                $notificationClass = $type === 'order_status' 
                    ? 'App\\Notifications\\OrderStatusUpdatedNotification'
                    : 'App\\Notifications\\OrderValidatedNotification';
                $query->where('type', $notificationClass);
            }

            // Filter by read status
            if ($readStatus === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($readStatus === 'unread') {
                $query->whereNull('read_at');
            }

            // Get all notifications
            $notifications = $query->orderBy('created_at', 'desc')->get();

            // Transform notifications data
            $transformedNotifications = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'notifiable_type' => $notification->notifiable_type,
                    'notifiable_id' => $notification->notifiable_id,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'updated_at' => $notification->updated_at,
                    'notification_type' => $this->getNotificationType($notification->type),
                    'is_read' => !is_null($notification->read_at)
                ];
            });

            // Get summary statistics
            $summary = $this->getNotificationsSummary($user);

            return response()->json([
                'message' => 'Notifications retrieved successfully',
                'data' => [
                    'notifications' => $transformedNotifications
                ],
                'summary' => $summary
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve notifications',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/order-status",
     *     summary="Get Order Status Notifications",
     *     description="Retrieves only order status update notifications for the authenticated user.",
     *     operationId="getOrderStatusNotifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of notifications per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order status notifications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order status notifications retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="order_id", type="integer", example=123),
     *                     @OA\Property(property="message", type="string", example="Order #123 status updated to: delivered"),
     *                     @OA\Property(property="status_message", type="string", example="Your order has been delivered"),
     *                     @OA\Property(property="old_status", type="string", example="processing"),
     *                     @OA\Property(property="new_status", type="string", example="delivered"),
     *                     @OA\Property(property="supplier_name", type="string", example="Tech Store"),
     *                     @OA\Property(property="is_read", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getOrderStatusNotifications(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);

            $notifications = $user->notifications()
                ->where('type', 'App\\Notifications\\OrderStatusUpdatedNotification')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $transformedData = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'order_id' => $notification->data['order_id'],
                    'message' => $notification->data['message'],
                    'status_message' => $notification->data['status_message'],
                    'old_status' => $notification->data['old_status'],
                    'new_status' => $notification->data['new_status'],
                    'supplier_name' => $notification->data['supplier_name'],
                    'is_read' => !is_null($notification->read_at),
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at
                ];
            });

            return response()->json([
                'message' => 'Order status notifications retrieved successfully',
                'data' => $transformedData,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve order status notifications',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/order-validated",
     *     summary="Get Order Validated Notifications",
     *     description="Retrieves only order validation notifications for suppliers (authenticated user must be a supplier).",
     *     operationId="getOrderValidatedNotifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of notifications per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order validated notifications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order validated notifications retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="order_id", type="integer", example=123),
     *                     @OA\Property(property="message", type="string", example="New order #123 from Ahmed Benali"),
     *                     @OA\Property(property="customer_name", type="string", example="Ahmed Benali"),
     *                     @OA\Property(property="customer_phone", type="string", example="+213661234567"),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="is_read", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getOrderValidatedNotifications(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);

            $notifications = $user->notifications()
                ->where('type', 'App\\Notifications\\OrderValidatedNotification')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $transformedData = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'order_id' => $notification->data['order_id'],
                    'message' => $notification->data['message'],
                    'customer_name' => $notification->data['customer_name'],
                    'customer_phone' => $notification->data['customer_phone'],
                    'status' => $notification->data['status'],
                    'is_read' => !is_null($notification->read_at),
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at
                ];
            });

            return response()->json([
                'message' => 'Order validated notifications retrieved successfully',
                'data' => $transformedData,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve order validated notifications',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread",
     *     summary="Get Unread Notifications",
     *     description="Retrieves only unread notifications for the authenticated user.",
     *     operationId="getUnreadNotifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unread notifications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unread notifications retrieved successfully"),
     *             @OA\Property(property="count", type="integer", example=5),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="data", type="object"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="notification_type", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getUnreadNotifications()
    {
        try {
            $user = Auth::user();
            
            $notifications = $user->unreadNotifications()
                ->orderBy('created_at', 'desc')
                ->get();

            $transformedData = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at,
                    'notification_type' => $this->getNotificationType($notification->type)
                ];
            });

            return response()->json([
                'message' => 'Unread notifications retrieved successfully',
                'count' => $notifications->count(),
                'data' => $transformedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve unread notifications',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/notifications/{id}/mark-as-read",
     *     summary="Mark Notification as Read",
     *     description="Marks a specific notification as read for the authenticated user.",
     *     operationId="markNotificationAsRead",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification marked as read")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification not found")
     *         )
     *     )
     * )
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            $notification = $user->notifications()->where('id', $id)->first();
            
            if (!$notification) {
                return response()->json(['message' => 'Notification not found'], 404);
            }

            $notification->markAsRead();

            return response()->json(['message' => 'Notification marked as read'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark notification as read',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/notifications/mark-all-as-read",
     *     summary="Mark All Notifications as Read",
     *     description="Marks all notifications as read for the authenticated user.",
     *     operationId="markAllNotificationsAsRead",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="All notifications marked as read"),
     *             @OA\Property(property="count", type="integer", example=5)
     *         )
     *     )
     * )
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            
            $count = $user->unreadNotifications()->count();
            $user->unreadNotifications->markAsRead();

            return response()->json([
                'message' => 'All notifications marked as read',
                'count' => $count
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark all notifications as read',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     summary="Delete Notification",
     *     description="Deletes a specific notification for the authenticated user.",
     *     operationId="deleteNotification",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification not found")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            $notification = $user->notifications()->where('id', $id)->first();
            
            if (!$notification) {
                return response()->json(['message' => 'Notification not found'], 404);
            }

            $notification->delete();

            return response()->json(['message' => 'Notification deleted successfully'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete notification',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

    /**
     * Get notification type string from class name
     */
    private function getNotificationType($className)
    {
        if (str_contains($className, 'OrderStatusUpdatedNotification')) {
            return 'order_status';
        } elseif (str_contains($className, 'OrderValidatedNotification')) {
            return 'order_validated';
        }
        return 'unknown';
    }

    /**
     * Get notifications summary statistics
     */
    private function getNotificationsSummary($user)
    {
        $totalNotifications = $user->notifications()->count();
        $unreadCount = $user->unreadNotifications()->count();
        $orderStatusCount = $user->notifications()
            ->where('type', 'App\\Notifications\\OrderStatusUpdatedNotification')
            ->count();
        $orderValidatedCount = $user->notifications()
            ->where('type', 'App\\Notifications\\OrderValidatedNotification')
            ->count();

        return [
            'total_notifications' => $totalNotifications,
            'unread_count' => $unreadCount,
            'order_status_count' => $orderStatusCount,
            'order_validated_count' => $orderValidatedCount
        ];
    }
}