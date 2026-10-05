<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AnnouncementAudience: string
{
    use HasOptions;

    case Everyone = 'everyone';
    case Members = 'members';
    case Staff = 'staff';
}
