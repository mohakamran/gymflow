<?php

namespace App\Notifications\Channels;

class WhatsAppChannel extends TextMessageChannel
{
    protected function channel(): string
    {
        return 'whatsapp';
    }
}
