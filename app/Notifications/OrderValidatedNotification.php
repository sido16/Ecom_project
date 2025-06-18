<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderValidatedNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
        Log::info('OrderValidatedNotification constructed', [
            'order_id' => $order->id,
            'supplier_id' => $order->supplier_id,
            'order_data' => $order->toArray(),
            'notification_class' => get_class($this),
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    public function via($notifiable)
    {
        Log::info('Notification via method called', [
            'notifiable_id' => $notifiable->id,
            'notifiable_type' => get_class($notifiable),
            'notifiable_data' => $notifiable->toArray(),
            'channels' => ['database'],
            'notification_class' => get_class($this),
            'timestamp' => now()->toDateTimeString()
        ]);
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        try {
            Log::info('Starting toDatabase method', [
                'notifiable_id' => $notifiable->id,
                'notifiable_type' => get_class($notifiable),
                'notification_class' => get_class($this),
                'timestamp' => now()->toDateTimeString()
            ]);

            // Prepare the notification data
            $notificationData = [
                'order_id' => $this->order->id,
                'message' => sprintf(
                    'New order #%d received from %s',
                    $this->order->id,
                    $this->order->full_name
                ),
                'url' => route('supplier.orders.show', $this->order->id),
                'type' => 'new_order',
                'order_details' => [
                    'total_items' => $this->order->orderProducts->sum('quantity'),
                    'status' => $this->order->status,
                    'phone_number' => $this->order->phone_number,
                    'address' => $this->order->address,
                ],
                'created_at' => now()->toDateTimeString(),
            ];

            // Log the data that will be stored
            Log::info('Notification data structure', [
                'notification_type' => get_class($this),
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'data' => $notificationData,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Verify table structure
            $columns = DB::getSchemaBuilder()->getColumnListing('notifications');
            Log::info('Notifications table structure', [
                'columns' => $columns,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Check if a notification with this data already exists
            $existingNotification = DB::table('notifications')
                ->where('notifiable_type', get_class($notifiable))
                ->where('notifiable_id', $notifiable->id)
                ->where('type', get_class($this))
                ->whereJsonContains('data->order_id', $this->order->id)
                ->first();

            if ($existingNotification) {
                Log::info('Similar notification already exists', [
                    'notification_id' => $existingNotification->id,
                    'created_at' => $existingNotification->created_at,
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Log the current state of notifications for this notifiable
            $notifiableNotifications = DB::table('notifications')
                ->where('notifiable_type', get_class($notifiable))
                ->where('notifiable_id', $notifiable->id)
                ->get();

            Log::info('Current notifications for notifiable', [
                'notifiable_id' => $notifiable->id,
                'notifiable_type' => get_class($notifiable),
                'total_notifications' => $notifiableNotifications->count(),
                'notifications' => $notifiableNotifications->toArray(),
                'timestamp' => now()->toDateTimeString()
            ]);

            return $notificationData;
        } catch (\Exception $e) {
            Log::error('Error in toDatabase method', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'notifiable_id' => $notifiable->id ?? 'null',
                'notifiable_type' => isset($notifiable) ? get_class($notifiable) : 'null',
                'notification_class' => get_class($this),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            return [
                'order_id' => $this->order->id,
                'message' => 'New order received',
                'type' => 'new_order',
                'created_at' => now()->toDateTimeString(),
            ];
        }
    }
}