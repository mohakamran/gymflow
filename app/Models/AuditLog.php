<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'tenant_id', 'user_id', 'event', 'auditable_type', 'auditable_id', 'description',
    'old_values', 'new_values', 'ip_address', 'user_agent',
])]
class AuditLog extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed()->withoutGlobalScopes();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Human-readable subject type, e.g. "Tenant" or "User".
     */
    public function subjectLabel(): ?string
    {
        return $this->auditable_type ? class_basename($this->auditable_type) : null;
    }

    public function eventColor(): string
    {
        return match (true) {
            str_contains($this->event, 'deleted'), str_contains($this->event, 'failed'), str_contains($this->event, 'suspended') => 'rose',
            str_contains($this->event, 'created'), str_contains($this->event, 'login') => 'emerald',
            str_contains($this->event, 'updated') => 'sky',
            default => 'zinc',
        };
    }
}
