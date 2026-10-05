<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::AnnouncementsManage);
    }

    public function view(User $user, Announcement $model): bool
    {
        return $this->allows($user, $model, Permission::AnnouncementsManage);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::AnnouncementsManage);
    }

    public function update(User $user, Announcement $model): bool
    {
        return $this->allows($user, $model, Permission::AnnouncementsManage);
    }

    public function delete(User $user, Announcement $model): bool
    {
        return $this->allows($user, $model, Permission::AnnouncementsManage);
    }
}
