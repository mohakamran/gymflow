<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MembershipStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'sky',
            self::Active => 'emerald',
            self::Suspended => 'amber',
            self::Expired => 'rose',
            self::Cancelled => 'zinc',
        };
    }
}
