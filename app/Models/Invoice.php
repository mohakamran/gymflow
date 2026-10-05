<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\InvoiceStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'member_id', 'membership_id', 'number', 'issued_on', 'due_on', 'currency', 'subtotal', 'discount_total',
    'tax_total', 'total', 'amount_paid', 'status', 'notes', 'created_by',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use Auditable, BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_on' => DateOnly::class,
            'due_on' => DateOnly::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'sent_at' => 'datetime',
            'due_reminder_sent_at' => 'datetime',
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
     * @return BelongsTo<Membership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->open()->where('due_on', '<', tenant_today()->toDateString());
    }

    public function balance(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen() && $this->due_on->lt(tenant_today());
    }
}
