<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Membership;
use App\Models\User;

class MembershipPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MembershipsView);
    }

    public function view(User $user, Membership $model): bool
    {
        return $this->allows($user, $model, Permission::MembershipsView);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MembershipsManage);
    }

    public function update(User $user, Membership $model): bool
    {
        return $this->allows($user, $model, Permission::MembershipsManage);
    }

    public function delete(User $user, Membership $model): bool
    {
        return $this->allows($user, $model, Permission::MembershipsManage);
    }
}
