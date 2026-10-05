<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Gender: string
{
    use HasOptions;

    case Female = 'female';
    case Male = 'male';
    case NonBinary = 'non_binary';
    case PreferNotToSay = 'prefer_not_to_say';
}
