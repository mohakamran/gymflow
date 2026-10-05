<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\ClassSessionStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['gym_class_id', 'trainer_id', 'series_id', 'starts_at', 'ends_at', 'capacity', 'location', 'status'])]
class ClassSession extends Model
{
    use BelongsToTenant;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'status' => ClassSessionStatus::class,
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GymClass, $this>
     */
    public function gymClass(): BelongsTo
    {
        return $this->belongsTo(GymClass::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id')->withTrashed();
    }

    /**
     * @return HasMany<ClassBooking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    /**
     * @return HasMany<ClassBooking, $this>
     */
    public function activeBookings(): HasMany
    {
        return $this->bookings()->whereIn('status', BookingStatus::occupying());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now())->where('status', ClassSessionStatus::Scheduled->value)->orderBy('starts_at');
    }

    public function spotsLeft(): int
    {
        $booked = $this->active_bookings_count ?? $this->activeBookings()->count();

        return max(0, $this->capacity - $booked);
    }

    public function isFull(): bool
    {
        return $this->spotsLeft() === 0;
    }

    public function isBookable(): bool
    {
        return $this->status === ClassSessionStatus::Scheduled && $this->starts_at->isFuture();
    }
}
