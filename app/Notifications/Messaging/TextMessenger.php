<?php

namespace App\Notifications\Messaging;

/**
 * Sends a plain-text message over SMS or WhatsApp. Implement this for a real provider
 * (Twilio, Vonage, a local SMS gateway, the WhatsApp Business API) and register it in
 * config/gym.php under text_messaging.drivers.
 */
interface TextMessenger
{
    /**
     * @param  'sms'|'whatsapp'  $channel
     */
    public function send(string $channel, string $to, string $message): void;
}
