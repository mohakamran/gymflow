<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['category', 'title', 'amount', 'spent_on', 'vendor', 'payment_method', 'reference', 'notes', 'recorded_by'])]
class Expense extends Model
{
    use Auditable, BelongsToTenant, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'spent_on' => DateOnly::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }
}
