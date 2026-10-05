<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\GymClass;
use App\Models\User;

class GymClassPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ClassesView);
    }

    public function view(User $user, GymClass $model): bool
    {
        return $this->allows($user, $model, Permission::ClassesView);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ClassesManage);
    }

    public function update(User $user, GymClass $model): bool
    {
        return $this->allows($user, $model, Permission::ClassesManage);
    }

    public function delete(User $user, GymClass $model): bool
    {
        return $this->allows($user, $model, Permission::ClassesManage);
    }
}
