<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id', 'invoice_id', 'reference', 'amount', 'refunded_amount', 'method', 'status', 'gateway',
    'gateway_reference', 'paid_at', 'received_by', 'notes',
])]
class Payment extends Model
{
    use Auditable, BelongsToTenant;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
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
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by')->withTrashed();
    }

    /**
     * Payments that count towards revenue (completed or partially refunded).
     *
     * @param  Builder<self>  $query
     */
    public function scopeSuccessful(Builder $query): void
    {
        $query->whereIn('status', [PaymentStatus::Completed->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value]);
    }

    public function netAmount(): float
    {
        return round((float) $this->amount - (float) $this->refunded_amount, 2);
    }

    public function refundableAmount(): float
    {
        return max(0, $this->netAmount());
    }
}
