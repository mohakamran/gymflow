<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MemberStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Inactive => 'zinc',
            self::Suspended => 'rose',
        };
    }
}
