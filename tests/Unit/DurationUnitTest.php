<?php

namespace Tests\Unit;

use App\Casts\DateOnly;
use App\Enums\DurationUnit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DurationUnitTest extends TestCase
{
    /**
     * @return array<string, array{DurationUnit, int, string, string}>
     */
    public static function periods(): array
    {
        return [
            '1 day' => [DurationUnit::Day, 1, '2026-01-10', '2026-01-10'],
            '2 weeks' => [DurationUnit::Week, 2, '2026-01-01', '2026-01-14'],
            '1 month' => [DurationUnit::Month, 1, '2026-01-15', '2026-02-14'],
            'month from Jan 31 does not overflow' => [DurationUnit::Month, 1, '2026-01-31', '2026-02-27'],
            '3 months' => [DurationUnit::Month, 3, '2026-10-05', '2027-01-04'],
            '1 year' => [DurationUnit::Year, 1, '2026-03-01', '2027-02-28'],
        ];
    }

    #[DataProvider('periods')]
    public function test_end_date_is_inclusive(DurationUnit $unit, int $value, string $start, string $expectedEnd): void
    {
        $this->assertSame($expectedEnd, $unit->endDate(CarbonImmutable::parse($start), $value)->toDateString());
    }

    public function test_describe_pluralises(): void
    {
        $this->assertSame('1 month', DurationUnit::Month->describe(1));
        $this->assertSame('3 months', DurationUnit::Month->describe(3));
    }

    public function test_date_only_cast_stores_plain_dates(): void
    {
        $cast = new DateOnly;
        $model = new class extends Model {};

        $this->assertSame('2026-10-05', $cast->set($model, 'starts_on', CarbonImmutable::parse('2026-10-05 18:30:00'), []));
        $this->assertSame('2026-10-05', $cast->set($model, 'starts_on', '2026-10-05', []));
        $this->assertNull($cast->set($model, 'starts_on', '', []));
        $this->assertSame('2026-10-05 00:00:00', $cast->get($model, 'starts_on', '2026-10-05', [])->toDateTimeString());
    }
}
