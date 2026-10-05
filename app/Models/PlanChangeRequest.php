<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['requested_by', 'current_plan', 'requested_plan', 'status', 'message'])]
class PlanChangeRequest extends Model
{
    use BelongsToTenant;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_plan' => SubscriptionPlan::class,
            'requested_plan' => SubscriptionPlan::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed()->withoutGlobalScopes();
    }
}
