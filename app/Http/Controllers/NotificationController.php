<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // List all notifications for the authenticated user
    public function index(Request $request)
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(20);
        $data = $notifications->getCollection()->map(function ($notification) {
            $user = $notification->data['user'] ?? null;
            return [
                'full_name' => $user['full_name'] ?? null,
                'picture' => $user['picture'] ?? null,
            ];
        });
        return response()->json([
            'data' => $data,
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
        ]);
    }

    // Mark a specific notification as read
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['message' => 'Notification marked as read']);
    }

    // Mark all notifications as read
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All notifications marked as read']);
    }

    // Delete a notification
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();
        return response()->json(['message' => 'Notification deleted']);
    }

    // Get only order status update notifications for the authenticated user
    public function getOrderStatusNotifications(Request $request)
    {
        $notifications = Auth::user()->notifications()
            ->where('type', 'App\\Notifications\\OrderStatusUpdatedNotification')
            ->latest()
            ->paginate(20);
        $data = $notifications->getCollection()->map(function ($notification) {
            $user = $notification->data['user'] ?? null;
            return [
                'order_id' => $notification->data['order_id'] ?? null,
                'old_status' => $notification->data['old_status'] ?? null,
                'new_status' => $notification->data['new_status'] ?? null,
                'message' => $notification->data['message'] ?? null,
                'full_name' => $user['full_name'] ?? null,
                'picture' => $user['picture'] ?? null,
            ];
        });
        return response()->json([
            'data' => $data,
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
        ]);
    }
} 