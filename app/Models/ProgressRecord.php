<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['member_id', 'recorded_by', 'recorded_on', 'weight_kg', 'body_fat_percent', 'chest_cm', 'waist_cm', 'hips_cm', 'arms_cm', 'thighs_cm', 'notes'])]
class ProgressRecord extends Model
{
    use BelongsToTenant;

    public const MEASUREMENTS = [
        'weight_kg' => 'Weight (kg)',
        'body_fat_percent' => 'Body fat (%)',
        'chest_cm' => 'Chest (cm)',
        'waist_cm' => 'Waist (cm)',
        'hips_cm' => 'Hips (cm)',
        'arms_cm' => 'Arms (cm)',
        'thighs_cm' => 'Thighs (cm)',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(['recorded_on' => DateOnly::class], array_fill_keys(array_keys(self::MEASUREMENTS), 'decimal:2'));
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }
}
