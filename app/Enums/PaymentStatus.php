<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PaymentStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Completed = 'completed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Failed = 'failed';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'sky',
            self::Completed => 'emerald',
            self::PartiallyRefunded, self::Refunded => 'amber',
            self::Failed => 'rose',
        };
    }
}
