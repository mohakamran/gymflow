<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ExpenseCategory: string
{
    use HasOptions;

    case Rent = 'rent';
    case Utilities = 'utilities';
    case Equipment = 'equipment';
    case Salaries = 'salaries';
    case Maintenance = 'maintenance';
    case Marketing = 'marketing';
    case Other = 'other';
}
