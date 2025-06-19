<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    public $order;
    public $oldStatus;
    public $newStatus;

    public function __construct($order, $oldStatus, $newStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'type' => 'status_update',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    // Method to get dynamic data when displaying notification
    public function getDynamicData()
    {
        $order = \App\Models\Order::with(['supplier'])->find($this->order->id);
        
        if (!$order) {
            return null;
        }

        // Get supplier/store name and picture
        $storeName = $order->supplier->business_name ?? 'Unknown Store';
        $storePicture = $order->supplier->logo ?? null;

        $statusMessages = [
            'pending' => 'Your order is pending confirmation',
            'processing' => 'Your order is being processed',
            'delivered' => 'Your order has been delivered'
        ];

        return [
            'store_name' => $storeName,
            'store_picture' => $storePicture,
            'message' => "Order #{$order->id} status updated to: {$this->newStatus}",
            'status_message' => $statusMessages[$this->newStatus] ?? "Status updated to {$this->newStatus}",
        ];
    }
}