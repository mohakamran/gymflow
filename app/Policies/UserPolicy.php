<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Team management (staff, trainers, owners) inside a gym.
 */
class UserPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::StaffManage);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::StaffManage);
    }

    public function update(User $user, User $model): bool
    {
        return $this->allows($user, $model, Permission::StaffManage);
    }
}
