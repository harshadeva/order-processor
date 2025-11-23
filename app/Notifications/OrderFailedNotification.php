<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\LogMessage;
use Illuminate\Support\Facades\Log;
class OrderFailedNotification extends Notification
{
    use Queueable;

     public function __construct(protected Order $order)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        Log::channel('notification-log')->info('Order failed', [
            'order_id' => $this->order->id,
            'customer_id' => $this->order->customer_id,
        ]);
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('Your order has failed to process.')
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id'    => $this->order->id,
            'order_code'    => $this->order->code,
            'customer_id' => $this->order->customer_id,
            'status'      => $this->order->status,
            'total'       => $this->order->total,
        ];
    }
}
