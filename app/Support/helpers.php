<?php

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

if (! function_exists('tenant')) {
    /**
     * The gym active for this request or job, if any.
     */
    function tenant(): ?Tenant
    {
        return app(TenantContext::class)->get();
    }
}

if (! function_exists('tenant_timezone')) {
    function tenant_timezone(): string
    {
        return tenant()?->timezone ?? config('app.timezone');
    }
}

if (! function_exists('tenant_now')) {
    /**
     * Current time in the gym's timezone. Timestamps are stored in UTC.
     */
    function tenant_now(): CarbonImmutable
    {
        return CarbonImmutable::now(tenant_timezone());
    }
}

if (! function_exists('tenant_today')) {
    /**
     * Today's date for the gym. Date columns (start/end dates) are stored as gym-local dates.
     */
    function tenant_today(): CarbonImmutable
    {
        return tenant_now()->startOfDay();
    }
}

if (! function_exists('money')) {
    /**
     * Format an amount in the gym's (or the given) currency, e.g. "$1,250.00".
     */
    function money(float|int|string|null $amount, ?string $currency = null): string
    {
        $currency ??= tenant()?->currency ?? 'USD';
        $symbol = config("gym.currencies.$currency.0", $currency.' ');
        $value = (float) $amount;

        return ($value < 0 ? '-' : '').$symbol.number_format(abs($value), 2);
    }
}

if (! function_exists('format_date')) {
    /**
     * Format a date using the gym's preferred date format.
     */
    function format_date(?CarbonInterface $date, bool $withTime = false): string
    {
        if ($date === null) {
            return '—';
        }

        $format = tenant()?->setting('date_format', 'M j, Y') ?? 'M j, Y';

        if ($withTime) {
            return $date->copy()->setTimezone(tenant_timezone())->format($format.', g:i A');
        }

        return $date->format($format);
    }
}

if (! function_exists('format_time')) {
    function format_time(?CarbonInterface $date): string
    {
        return $date ? $date->copy()->setTimezone(tenant_timezone())->format('g:i A') : '—';
    }
}
