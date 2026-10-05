<?php

namespace App\Enums\Concerns;

/**
 * Shared helpers for backed enums that expose label() and color().
 */
trait HasOptions
{
    /**
     * @return array<string, string> value => label, ready for <x-select :options>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return str($this->name)->headline()->toString();
    }

    public function color(): string
    {
        return 'zinc';
    }
}
