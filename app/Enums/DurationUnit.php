<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Carbon\CarbonInterface;

enum DurationUnit: string
{
    use HasOptions;

    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    /**
     * Last day covered by a period of $value units starting on $start (inclusive).
     */
    public function endDate(CarbonInterface $start, int $value): CarbonInterface
    {
        $start = $start->toImmutable();

        $next = match ($this) {
            self::Day => $start->addDays($value),
            self::Week => $start->addWeeks($value),
            self::Month => $start->addMonthsNoOverflow($value),
            self::Year => $start->addYearsNoOverflow($value),
        };

        return $next->subDay();
    }

    public function describe(int $value): string
    {
        return $value.' '.str($this->value)->plural($value);
    }
}
