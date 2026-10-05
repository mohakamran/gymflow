<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['equipment_id', 'performed_on', 'type', 'cost', 'performed_by', 'notes', 'recorded_by'])]
class EquipmentMaintenance extends Model
{
    use BelongsToTenant;

    public const TYPES = ['inspection' => 'Inspection', 'service' => 'Service', 'repair' => 'Repair', 'replacement' => 'Part replacement', 'cleaning' => 'Deep clean'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_on' => DateOnly::class,
            'cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Equipment, $this>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class)->withTrashed();
    }
}
