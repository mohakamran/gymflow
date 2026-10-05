<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Calendar date stored as "Y-m-d" on every database driver.
 *
 * Laravel's built-in "date" cast stores "Y-m-d 00:00:00", which breaks string comparisons
 * such as `ends_on >= '2026-10-06'` on SQLite. This cast keeps the stored value a pure date
 * while still returning a Carbon instance.
 *
 * @implements CastsAttributes<Carbon|null, mixed>
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ($value instanceof \DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value))->toDateString();
    }
}
