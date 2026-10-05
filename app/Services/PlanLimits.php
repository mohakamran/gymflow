<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;

/**
 * Enforces the SaaS subscription limits (members and staff accounts) for a gym.
 */
class PlanLimits
{
    /**
     * @return array{members: array{used: int, limit: int|null}, staff: array{used: int, limit: int|null}}
     */
    public function usage(Tenant $tenant): array
    {
        return [
            'members' => ['used' => $this->activeMembers($tenant), 'limit' => $tenant->subscription_plan->memberLimit()],
            'staff' => ['used' => $this->staffAccounts($tenant), 'limit' => $tenant->subscription_plan->staffLimit()],
        ];
    }

    public function ensureCanAddMember(Tenant $tenant): void
    {
        $limit = $tenant->subscription_plan->memberLimit();

        if ($limit !== null && $this->activeMembers($tenant) >= $limit) {
            throw new BusinessRuleException("Your {$tenant->subscription_plan->label()} plan allows {$limit} active members. Upgrade your plan to add more.");
        }
    }

    public function ensureCanAddStaff(Tenant $tenant): void
    {
        $limit = $tenant->subscription_plan->staffLimit();

        if ($limit !== null && $this->staffAccounts($tenant) >= $limit) {
            throw new BusinessRuleException("Your {$tenant->subscription_plan->label()} plan allows {$limit} team accounts. Upgrade your plan to invite more.");
        }
    }

    protected function activeMembers(Tenant $tenant): int
    {
        return Member::withoutTenancy()->where('tenant_id', $tenant->id)->where('status', MemberStatus::Active->value)->count();
    }

    protected function staffAccounts(Tenant $tenant): int
    {
        return User::withoutTenancy()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->role([Role::Owner->value, Role::Staff->value, Role::Trainer->value])
            ->count();
    }
}
