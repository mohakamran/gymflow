<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name', 'slug', 'email', 'phone', 'website', 'address_line', 'city', 'state', 'postal_code',
    'country', 'primary_color', 'currency', 'timezone', 'locale', 'business_hours', 'settings',
    'public_profile_enabled',
])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use Auditable, HasFactory, SoftDeletes;

    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    /**
     * @var list<string>
     */
    protected array $auditExclude = ['invoice_counter', 'member_counter'];

    /**
     * Mirrors the column defaults so freshly created models are complete.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'primary_color' => '#4f46e5',
        'currency' => 'USD',
        'timezone' => 'UTC',
        'locale' => 'en',
        'status' => 'active',
        'subscription_plan' => 'starter',
        'public_profile_enabled' => false,
        'invoice_counter' => 0,
        'member_counter' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'business_hours' => 'array',
            'settings' => 'array',
            'status' => TenantStatus::class,
            'subscription_plan' => SubscriptionPlan::class,
            'trial_ends_at' => 'datetime',
            'public_profile_enabled' => 'boolean',
            'invoice_counter' => 'integer',
            'member_counter' => 'integer',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function faviconUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->favicon_path ? Storage::disk('public')->url($this->favicon_path) : null);
    }

    /**
     * Two-letter monogram used when no logo has been uploaded.
     *
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::get(function (): string {
            $words = preg_split('/\s+/', trim($this->name)) ?: [];

            return strtoupper(mb_substr($words[0] ?? 'G', 0, 1).mb_substr($words[1] ?? '', 0, 1));
        });
    }

    /**
     * Read a value from the settings JSON column using dot notation.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    /**
     * @return array<string, array{open: string|null, close: string|null, closed: bool}>
     */
    public static function defaultBusinessHours(): array
    {
        return collect(self::DAYS)->mapWithKeys(fn (string $day): array => [
            $day => [
                'open' => $day === 'sunday' ? null : '06:00',
                'close' => $day === 'sunday' ? null : '22:00',
                'closed' => $day === 'sunday',
            ],
        ])->all();
    }

    public function isOnTrial(): bool
    {
        return $this->status === TenantStatus::Trial && $this->trial_ends_at?->isFuture();
    }
}
