<?php

namespace App\Models;

use App\Enums\DurationUnit;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MembershipPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'description', 'duration_value', 'duration_unit', 'price', 'signup_fee', 'discount_percent',
    'is_taxable', 'features', 'class_limit_per_week', 'access_hours', 'color', 'is_active', 'is_public', 'sort_order',
])]
class MembershipPlan extends Model
{
    /** @use HasFactory<MembershipPlanFactory> */
    use Auditable, BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_unit' => DurationUnit::class,
            'duration_value' => 'integer',
            'price' => 'decimal:2',
            'signup_fee' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'is_taxable' => 'boolean',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function durationLabel(): string
    {
        return $this->duration_unit->describe($this->duration_value);
    }

    /**
     * Price after the plan's own discount, before tax.
     */
    public function effectivePrice(): float
    {
        return round((float) $this->price * (1 - (float) $this->discount_percent / 100), 2);
    }
}
