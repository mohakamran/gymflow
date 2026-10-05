<?php

namespace App\Notifications\Channels;

use App\Notifications\Messaging\TextMessenger;
use Illuminate\Notifications\Notification;

abstract class TextMessageChannel
{
    public function __construct(protected TextMessenger $messenger) {}

    /**
     * @return 'sms'|'whatsapp'
     */
    abstract protected function channel(): string;

    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor($this->channel(), $notification);

        if (! $to || ! method_exists($notification, 'toTextMessage')) {
            return;
        }

        $this->messenger->send($this->channel(), $to, $notification->toTextMessage($notifiable));
    }
}
