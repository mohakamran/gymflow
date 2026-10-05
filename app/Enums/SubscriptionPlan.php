<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Starter = 'starter';
    case Professional = 'professional';
    case Business = 'business';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Monthly price in USD, used on the marketing page. Null means "contact sales".
     */
    public function monthlyPrice(): ?int
    {
        return match ($this) {
            self::Starter => 29,
            self::Professional => 79,
            self::Business => 149,
            self::Enterprise => null,
        };
    }

    /**
     * Maximum active members. Null means unlimited.
     */
    public function memberLimit(): ?int
    {
        return match ($this) {
            self::Starter => 150,
            self::Professional => 750,
            self::Business => 3000,
            self::Enterprise => null,
        };
    }

    /**
     * Maximum staff accounts (owner, staff, trainers). Null means unlimited.
     */
    public function staffLimit(): ?int
    {
        return match ($this) {
            self::Starter => 3,
            self::Professional => 15,
            self::Business => 50,
            self::Enterprise => null,
        };
    }

    /**
     * @return list<string>
     */
    public function highlights(): array
    {
        return match ($this) {
            self::Starter => ['Up to 150 members', '3 staff accounts', 'Memberships & attendance', 'Invoices & payments'],
            self::Professional => ['Up to 750 members', '15 staff accounts', 'Classes & trainers', 'Reports & exports'],
            self::Business => ['Up to 3,000 members', '50 staff accounts', 'Advanced analytics', 'Priority support'],
            self::Enterprise => ['Unlimited members', 'Unlimited staff', 'Custom integrations', 'Dedicated success manager'],
        };
    }
}
