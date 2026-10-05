<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EquipmentCondition: string
{
    use HasOptions;

    case New = 'new';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';
}
