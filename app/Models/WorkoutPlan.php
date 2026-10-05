<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['member_id', 'trainer_id', 'title', 'goal', 'starts_on', 'ends_on', 'exercises', 'notes', 'is_active'])]
class WorkoutPlan extends Model
{
    use BelongsToTenant;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => DateOnly::class,
            'ends_on' => DateOnly::class,
            'exercises' => 'array',
            'is_active' => 'boolean',
        ];
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
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id')->withTrashed();
    }

    /**
     * Exercises grouped by training day label.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function exercisesByDay(): array
    {
        return collect($this->exercises ?? [])->groupBy(fn (array $exercise): string => $exercise['day'] ?: 'Any day')->map->values()->all();
    }
}
