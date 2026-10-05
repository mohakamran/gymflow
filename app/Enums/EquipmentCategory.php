<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EquipmentCategory: string
{
    use HasOptions;

    case Cardio = 'cardio';
    case Strength = 'strength';
    case FreeWeights = 'free_weights';
    case Functional = 'functional';
    case Accessories = 'accessories';
    case Facility = 'facility';
    case Other = 'other';
}
