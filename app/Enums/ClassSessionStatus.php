<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ClassSessionStatus: string
{
    use HasOptions;

    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'sky',
            self::Cancelled => 'rose',
            self::Completed => 'emerald',
        };
    }
}
