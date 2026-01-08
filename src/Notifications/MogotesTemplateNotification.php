<?php

namespace Dantofema\MogotesLaravel\Notifications;

use Illuminate\Notifications\Notification;

class MogotesTemplateNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected string $template,
        protected string $channel,
        protected array $data = []
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [MogotesChannel::class];
    }

    /**
     * Get the Mogotes representation of the notification.
     */
    public function toMogotes(object $notifiable): MogotesMessage
    {
        return MogotesMessage::create($this->template, $this->channel)
            ->with($this->data);
    }
}
