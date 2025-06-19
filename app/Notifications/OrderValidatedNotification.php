<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class OrderValidatedNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        // Store only essential data - other info will be retrieved dynamically
        return [
            'order_id' => $this->order->id,
            'type' => 'new_order',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    // Method to get dynamic data when displaying notification
    public function getDynamicData()
    {
        $order = \App\Models\Order::with(['user'])->find($this->order->id);
        
        if (!$order) {
            return null;
        }

        // Get user name and picture
        $userName = $order->full_name ?? $order->user->name ?? 'Unknown Customer';
        $userPicture = $order->user->profile_picture ?? null;

        return [
            'user_name' => $userName,
            'user_picture' => $userPicture,
            'message' => "New order #{$order->id} received from {$userName}",
            'url' => route('supplier.orders.show', $order->id),
            'order_details' => [
                'total_items' => $order->orderProducts->sum('quantity'),
                'status' => $order->status,
                'phone_number' => $order->phone_number,
                'address' => $order->address,
            ],
        ];
    }
}