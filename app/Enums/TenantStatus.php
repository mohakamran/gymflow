<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial => 'amber',
            self::Active => 'emerald',
            self::Suspended => 'rose',
        };
    }

    public function canAccessApp(): bool
    {
        return $this !== self::Suspended;
    }
}
