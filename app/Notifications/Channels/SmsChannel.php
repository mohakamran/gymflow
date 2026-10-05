<?php

namespace App\Notifications\Channels;

class SmsChannel extends TextMessageChannel
{
    protected function channel(): string
    {
        return 'sms';
    }
}
