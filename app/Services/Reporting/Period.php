<?php

namespace App\Services\Reporting;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * A gym-local date range with helpers for bucketing timestamps by day or month.
 */
final readonly class Period
{
    public function __construct(public CarbonImmutable $from, public CarbonImmutable $to) {}

    public static function make(?string $from, ?string $to, int $defaultDays = 29): self
    {
        $timezone = tenant_timezone();
        $end = $to ? CarbonImmutable::parse($to, $timezone) : tenant_today();
        $start = $from ? CarbonImmutable::parse($from, $timezone) : $end->subDays($defaultDays);

        return new self($start->startOfDay(), $end->endOfDay());
    }

    public static function lastMonths(int $months): self
    {
        return new self(tenant_today()->subMonthsNoOverflow($months - 1)->startOfMonth(), tenant_today()->endOfDay());
    }

    /**
     * UTC bounds for timestamp columns.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function utc(): array
    {
        return [$this->from->utc(), $this->to->utc()];
    }

    /**
     * Gym-local date bounds for date columns.
     *
     * @return array{0: string, 1: string}
     */
    public function dates(): array
    {
        return [$this->from->toDateString(), $this->to->toDateString()];
    }

    public function isMonthly(): bool
    {
        return $this->from->diffInDays($this->to) > 62;
    }

    /**
     * Bucket key for a timestamp or date.
     */
    public function bucket(\DateTimeInterface|string $value): string
    {
        $local = is_string($value)
            ? CarbonImmutable::parse($value, tenant_timezone())
            : CarbonImmutable::instance($value)->setTimezone(tenant_timezone());

        return $this->isMonthly() ? $local->format('Y-m') : $local->toDateString();
    }

    /**
     * Every bucket in the period, in order, mapped to its display label.
     *
     * @return Collection<string, string>
     */
    public function buckets(): Collection
    {
        if ($this->isMonthly()) {
            return collect(CarbonPeriod::create($this->from->startOfMonth(), '1 month', $this->to->startOfMonth()))
                ->mapWithKeys(fn ($month) => [$month->format('Y-m') => $month->format('M Y')]);
        }

        return collect(CarbonPeriod::create($this->from->startOfDay(), '1 day', $this->to->startOfDay()))
            ->mapWithKeys(fn ($day) => [$day->toDateString() => $day->format('M j')]);
    }

    public function label(): string
    {
        return $this->from->format('M j, Y').' – '.$this->to->format('M j, Y');
    }
}
