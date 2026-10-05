<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'gender', 'address', 'emergency_contact_name',
    'emergency_contact_phone', 'joined_on', 'status', 'notes', 'trainer_id',
])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use Auditable, BelongsToTenant, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected array $auditExclude = ['check_in_token'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    public static function booted(): void
    {
        static::creating(function (Member $member): void {
            $member->check_in_token ??= Str::random(40);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => DateOnly::class,
            'joined_on' => DateOnly::class,
            'gender' => Gender::class,
            'status' => MemberStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * The most recent membership that is active or suspended.
     *
     * @return HasOne<Membership, $this>
     */
    public function currentMembership(): HasOne
    {
        return $this->hasOne(Membership::class)->ofMany(
            ['ends_on' => 'max', 'id' => 'max'],
            fn (Builder $query) => $query->whereIn('status', [MembershipStatus::Active->value, MembershipStatus::Suspended->value]),
        );
    }

    /**
     * @return HasOne<Membership, $this>
     */
    public function latestMembership(): HasOne
    {
        return $this->hasOne(Membership::class)->ofMany(['ends_on' => 'max', 'id' => 'max']);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<WorkoutPlan, $this>
     */
    public function workoutPlans(): HasMany
    {
        return $this->hasMany(WorkoutPlan::class);
    }

    /**
     * @return HasMany<ProgressRecord, $this>
     */
    public function progressRecords(): HasMany
    {
        return $this->hasMany(ProgressRecord::class);
    }

    /**
     * @return HasMany<MemberNote, $this>
     */
    public function memberNotes(): HasMany
    {
        return $this->hasMany(MemberNote::class);
    }

    /**
     * @return HasMany<MemberDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    /**
     * @return HasMany<ClassBooking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1)));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null);
    }

    public function routeNotificationForMail(?Notification $notification = null): ?string
    {
        return $this->email;
    }

    public function routeNotificationForSms(?Notification $notification = null): ?string
    {
        return $this->phone;
    }

    public function routeNotificationForWhatsapp(?Notification $notification = null): ?string
    {
        return $this->phone;
    }

    /**
     * Payload encoded in the member's check-in QR code.
     */
    public function checkInPayload(): string
    {
        return 'GYM:'.$this->check_in_token;
    }

    /**
     * Full-text-ish search over name, code, email and phone.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $fullName = $query->getConnection()->getDriverName() === 'sqlite'
            ? "(first_name || ' ' || last_name)"
            : "CONCAT(first_name, ' ', last_name)";

        $query->where(function (Builder $inner) use ($like, $fullName): void {
            $inner->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhereRaw("{$fullName} like ?", [$like])
                ->orWhere('member_code', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
        });
    }
}
