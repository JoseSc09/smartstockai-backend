<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LowStockNotification extends Notification
{
    use Queueable;

    protected $product;

    public function __construct($product)
    {
        $this->product = $product;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Alerta de Bajo Stock')
            ->line('El producto ' . $this->product->name . ' está bajo en stock.')
            ->line('Stock actual: ' . $this->product->stock)
            ->line('Por favor, considera reabastecer.');
    }
}
