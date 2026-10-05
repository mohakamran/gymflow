<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EquipmentStatus: string
{
    use HasOptions;

    case Active = 'active';
    case UnderMaintenance = 'under_maintenance';
    case Damaged = 'damaged';
    case Retired = 'retired';

    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::UnderMaintenance => 'amber',
            self::Damaged => 'rose',
            self::Retired => 'zinc',
        };
    }
}
