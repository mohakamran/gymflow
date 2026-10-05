<?php

namespace App\Models;

use App\Enums\AttendanceMethod;
use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['member_id', 'checked_in_at', 'checked_out_at', 'method', 'recorded_by', 'notes'])]
class Attendance extends Model
{
    use BelongsToTenant;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'method' => AttendanceMethod::class,
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }

    /**
     * Visits that started between two gym-local dates (inclusive), e.g. "2026-10-01".
     *
     * @param  Builder<self>  $query
     */
    public function scopeBetweenDays(Builder $query, string $from, ?string $to = null): void
    {
        $start = CarbonImmutable::parse($from, tenant_timezone())->startOfDay();
        $end = CarbonImmutable::parse($to ?? $from, tenant_timezone())->endOfDay();

        $query->whereBetween('checked_in_at', [$start->utc(), $end->utc()]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeToday(Builder $query): void
    {
        $this->scopeBetweenDays($query, tenant_today()->toDateString());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('checked_out_at');
    }

    public function durationMinutes(): ?int
    {
        return $this->checked_out_at ? (int) $this->checked_in_at->diffInMinutes($this->checked_out_at) : null;
    }
}
