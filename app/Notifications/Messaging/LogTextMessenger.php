<?php

namespace App\Notifications\Messaging;

use Illuminate\Support\Facades\Log;

/**
 * Development driver: writes messages to the log instead of sending them.
 */
class LogTextMessenger implements TextMessenger
{
    public function send(string $channel, string $to, string $message): void
    {
        Log::info("[{$channel}] to {$to}: {$message}");
    }
}
