<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ClassSession;
use App\Models\User;

class ClassSessionPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ClassesView);
    }

    public function view(User $user, ClassSession $model): bool
    {
        return $this->allows($user, $model, Permission::ClassesView);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ClassesManage);
    }

    public function update(User $user, ClassSession $model): bool
    {
        return $this->allows($user, $model, Permission::ClassesManage);
    }

    public function delete(User $user, ClassSession $model): bool
    {
        return $this->allows($user, $model, Permission::ClassesManage);
    }
}
