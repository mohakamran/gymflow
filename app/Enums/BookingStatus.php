<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BookingStatus: string
{
    use HasOptions;

    case Booked = 'booked';
    case Attended = 'attended';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';

    public function color(): string
    {
        return match ($this) {
            self::Booked => 'sky',
            self::Attended => 'emerald',
            self::NoShow => 'rose',
            self::Cancelled => 'zinc',
        };
    }

    /**
     * Statuses that take up a place in the class.
     *
     * @return list<string>
     */
    public static function occupying(): array
    {
        return [self::Booked->value, self::Attended->value];
    }
}
