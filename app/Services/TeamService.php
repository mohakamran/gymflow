<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AccountInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamService
{
    public function __construct(protected PlanLimits $limits) {}

    /**
     * Create a team account and email an invitation to set a password.
     *
     * @param  array{name: string, email: string, phone?: string|null, role: string}  $account
     * @param  array<string, mixed>  $profile
     */
    public function invite(Tenant $tenant, array $account, array $profile = []): User
    {
        $this->limits->ensureCanAddStaff($tenant);
        $role = $this->assertAssignable($account['role']);

        $user = DB::transaction(function () use ($tenant, $account, $profile, $role): User {
            $user = new User([
                'name' => $account['name'],
                'email' => $account['email'],
                'phone' => $account['phone'] ?? null,
                'password' => Str::password(32),
            ]);
            $user->tenant_id = $tenant->id;
            $user->email_verified_at = now();
            $user->save();
            $user->assignRole($role->value);

            $staffProfile = new StaffProfile(array_merge($profile, ['user_id' => $user->id]));
            $staffProfile->tenant_id = $tenant->id;
            $staffProfile->save();

            return $user;
        });

        $user->notify(new AccountInvitationNotification($tenant, 'team'));

        return $user;
    }

    /**
     * @param  array{name: string, phone?: string|null, role: string}  $account
     * @param  array<string, mixed>  $profile
     */
    public function update(User $user, array $account, array $profile, User $actor): User
    {
        $role = $this->assertAssignable($account['role']);

        if ($user->hasRole(Role::Owner->value) && $role !== Role::Owner) {
            $this->ensureNotLastOwner($user);

            if ($user->is($actor)) {
                throw new BusinessRuleException('You cannot remove your own owner role.');
            }
        }

        DB::transaction(function () use ($user, $account, $profile, $role): void {
            $user->update(['name' => $account['name'], 'phone' => $account['phone'] ?? null]);
            $user->syncRoles([$role->value]);

            $staffProfile = $user->staffProfile ?? new StaffProfile(['user_id' => $user->id]);
            $staffProfile->tenant_id = $user->tenant_id;
            $staffProfile->fill($profile)->save();
        });

        return $user;
    }

    public function setActive(User $user, bool $active, User $actor): User
    {
        if ($user->is($actor)) {
            throw new BusinessRuleException('You cannot deactivate your own account.');
        }

        if (! $active && $user->hasRole(Role::Owner->value)) {
            $this->ensureNotLastOwner($user);
        }

        if ($active) {
            $this->limits->ensureCanAddStaff(Tenant::findOrFail($user->tenant_id));
        }

        $user->update(['is_active' => $active]);

        if (! $active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return $user;
    }

    protected function assertAssignable(string $role): Role
    {
        $role = Role::tryFrom($role);

        if (! in_array($role, [Role::Owner, Role::Staff, Role::Trainer], true)) {
            throw new BusinessRuleException('Choose a valid team role.');
        }

        return $role;
    }

    protected function ensureNotLastOwner(User $user): void
    {
        $owners = User::query()->where('tenant_id', $user->tenant_id)->where('is_active', true)->role(Role::Owner->value)->count();

        if ($owners <= 1) {
            throw new BusinessRuleException('A gym needs at least one active owner.');
        }
    }
}
