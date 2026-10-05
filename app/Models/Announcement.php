<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'body', 'audience', 'is_pinned', 'published_at', 'created_by'])]
class Announcement extends Model
{
    use Auditable, BelongsToTenant;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => AnnouncementAudience::class,
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
            'emailed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForMembers(Builder $query): void
    {
        $query->whereIn('audience', [AnnouncementAudience::Everyone->value, AnnouncementAudience::Members->value]);
    }
}
