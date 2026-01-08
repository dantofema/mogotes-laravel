<?php

namespace Dantofema\MogotesLaravel\Notifications;

use Illuminate\Notifications\Notification;

class MogotesChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // @phpstan-ignore-next-line
        $message = $notification->toMogotes($notifiable);

        // TODO: Enviar usando MogotesClient
    }
}
