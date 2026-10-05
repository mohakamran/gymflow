<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AttendanceMethod: string
{
    use HasOptions;

    case Manual = 'manual';
    case Qr = 'qr';
    case Kiosk = 'kiosk';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Front desk',
            self::Qr => 'QR code',
            self::Kiosk => 'Kiosk',
        };
    }
}
