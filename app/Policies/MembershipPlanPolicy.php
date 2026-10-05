<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MembershipPlan;
use App\Models\User;

class MembershipPlanPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::PlansManage);
    }

    public function view(User $user, MembershipPlan $model): bool
    {
        return $this->allows($user, $model, Permission::PlansManage);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PlansManage);
    }

    public function update(User $user, MembershipPlan $model): bool
    {
        return $this->allows($user, $model, Permission::PlansManage);
    }

    public function delete(User $user, MembershipPlan $model): bool
    {
        return $this->allows($user, $model, Permission::PlansManage);
    }
}
