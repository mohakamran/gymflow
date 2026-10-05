<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\MembershipStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'member_id', 'membership_plan_id', 'renewed_from_id', 'starts_on', 'ends_on', 'status', 'price',
    'discount_amount', 'auto_renew', 'notes', 'created_by',
])]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use Auditable, BelongsToTenant, HasFactory;

    public const EXPIRING_WINDOW_DAYS = 7;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => DateOnly::class,
            'ends_on' => DateOnly::class,
            'suspended_on' => DateOnly::class,
            'cancelled_on' => DateOnly::class,
            'status' => MembershipStatus::class,
            'price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'auto_renew' => 'boolean',
            'expiry_reminder_sent_at' => 'datetime',
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
     * @return BelongsTo<MembershipPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id')->withTrashed();
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    /**
     * Active memberships whose end date falls within the reminder window.
     *
     * @param  Builder<self>  $query
     */
    public function scopeExpiringSoon(Builder $query, int $days = self::EXPIRING_WINDOW_DAYS): void
    {
        $query->where('status', MembershipStatus::Active->value)
            ->whereBetween('ends_on', [tenant_today()->toDateString(), tenant_today()->addDays($days)->toDateString()]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCurrentlyActive(Builder $query): void
    {
        $query->where('status', MembershipStatus::Active->value)
            ->where('starts_on', '<=', tenant_today()->toDateString())
            ->where('ends_on', '>=', tenant_today()->toDateString());
    }

    public function daysRemaining(): int
    {
        return max(0, (int) tenant_today()->diffInDays($this->ends_on, false));
    }

    public function isExpiringSoon(): bool
    {
        return $this->status === MembershipStatus::Active && $this->daysRemaining() <= self::EXPIRING_WINDOW_DAYS;
    }

    /**
     * Status for display: "Expiring" is derived from an active membership near its end.
     *
     * @return array{label: string, color: string}
     */
    public function displayStatus(): array
    {
        if ($this->status === MembershipStatus::Active && $this->ends_on->lt(tenant_today())) {
            return ['label' => 'Expired', 'color' => 'rose'];
        }

        if ($this->status === MembershipStatus::Active && $this->starts_on->gt(tenant_today())) {
            return ['label' => 'Upcoming', 'color' => 'sky'];
        }

        if ($this->isExpiringSoon()) {
            return ['label' => 'Expiring', 'color' => 'amber'];
        }

        return ['label' => $this->status->label(), 'color' => $this->status->color()];
    }

    public function progressPercent(): int
    {
        $total = max(1, $this->starts_on->diffInDays($this->ends_on) + 1);
        $elapsed = $this->starts_on->diffInDays(tenant_today()) + 1;

        return (int) max(0, min(100, round($elapsed / $total * 100)));
    }
}
