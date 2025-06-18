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

    public function toArray($notifiable)
    {
        $statusMessages = [
            'pending' => 'Your order is pending confirmation',
            'processing' => 'Your order is being processed',
            'delivered' => 'Your order has been delivered'
        ];

        return [
            'order_id' => $this->order->id,
            'message' => "Order #{$this->order->id} status updated to: {$this->newStatus}",
            'status_message' => $statusMessages[$this->newStatus] ?? "Status updated to {$this->newStatus}",
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'supplier_name' => $this->order->supplier->business_name ?? 'Unknown Supplier',
        ];
    }
}