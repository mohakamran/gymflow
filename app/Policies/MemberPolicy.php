<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Member;
use App\Models\User;

class MemberPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MembersView);
    }

    /**
     * Trainers without member-management rights only see the members assigned to them.
     */
    public function view(User $user, Member $member): bool
    {
        if (! $this->allows($user, $member, Permission::MembersView)) {
            return false;
        }

        return $user->can(Permission::MembersManage) || $member->trainer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MembersManage);
    }

    public function update(User $user, Member $member): bool
    {
        return $this->allows($user, $member, Permission::MembersManage);
    }

    public function delete(User $user, Member $member): bool
    {
        return $this->allows($user, $member, Permission::MembersManage) && $user->can(Permission::SettingsManage);
    }

    /**
     * Workout plans, progress and notes: managers, or the member's assigned trainer.
     */
    public function coach(User $user, Member $member): bool
    {
        if (! $this->sameTenant($user, $member)) {
            return false;
        }

        return $user->can(Permission::MembersManage)
            || ($user->can(Permission::WorkoutsManage) && ($member->trainer_id === $user->id || $user->can(Permission::SettingsManage)));
    }
}
