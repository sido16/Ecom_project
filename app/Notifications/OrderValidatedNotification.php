<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

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

    public function toArray($notifiable)
    {
        $user = $this->order->user;
        return [
            'order_id' => $this->order->id,
            'message' => 'A new order has been placed.',
            'user' => [
                'full_name' => $user ? $user->full_name : null,
                'picture' => $user ? $user->picture : null,
            ]
        ];
    }
} 