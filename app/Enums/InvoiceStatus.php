<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InvoiceStatus: string
{
    use HasOptions;

    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Void = 'void';

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'amber',
            self::Partial => 'sky',
            self::Paid => 'emerald',
            self::Refunded => 'zinc',
            self::Void => 'rose',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Unpaid, self::Partial], true);
    }
}
