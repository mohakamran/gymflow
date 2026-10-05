<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::EquipmentManage);
    }

    public function view(User $user, Equipment $model): bool
    {
        return $this->allows($user, $model, Permission::EquipmentManage);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::EquipmentManage);
    }

    public function update(User $user, Equipment $model): bool
    {
        return $this->allows($user, $model, Permission::EquipmentManage);
    }

    public function delete(User $user, Equipment $model): bool
    {
        return $this->allows($user, $model, Permission::EquipmentManage);
    }
}
