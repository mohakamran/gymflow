<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'category', 'quantity', 'purchased_on', 'cost', 'serial_number', 'location', 'condition', 'status',
    'last_maintained_on', 'next_maintenance_on', 'notes',
])]
class Equipment extends Model
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $table = 'equipment';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => EquipmentCategory::class,
            'condition' => EquipmentCondition::class,
            'status' => EquipmentStatus::class,
            'quantity' => 'integer',
            'cost' => 'decimal:2',
            'purchased_on' => DateOnly::class,
            'last_maintained_on' => DateOnly::class,
            'next_maintenance_on' => DateOnly::class,
            'maintenance_reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<EquipmentMaintenance, $this>
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(EquipmentMaintenance::class);
    }

    /**
     * Equipment in service whose next maintenance is due within $days (or overdue).
     *
     * @param  Builder<self>  $query
     */
    public function scopeMaintenanceDue(Builder $query, int $days = 7): void
    {
        $query->whereNotIn('status', [EquipmentStatus::Retired->value])
            ->whereNotNull('next_maintenance_on')
            ->where('next_maintenance_on', '<=', tenant_today()->addDays($days)->toDateString());
    }

    public function isMaintenanceOverdue(): bool
    {
        return $this->next_maintenance_on !== null && $this->status !== EquipmentStatus::Retired && $this->next_maintenance_on->lt(tenant_today());
    }
}
