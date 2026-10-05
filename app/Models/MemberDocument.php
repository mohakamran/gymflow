<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Documents are stored on the private "local" disk and only served through an authorized controller.
 */
#[Fillable(['member_id', 'uploaded_by', 'name', 'path', 'mime_type', 'size_bytes'])]
class MemberDocument extends Model
{
    use BelongsToTenant;

    public static function booted(): void
    {
        static::deleted(fn (MemberDocument $document) => Storage::disk('local')->delete($document->path));
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function humanSize(): string
    {
        return $this->size_bytes >= 1048576
            ? round($this->size_bytes / 1048576, 1).' MB'
            : max(1, (int) round($this->size_bytes / 1024)).' KB';
    }
}
