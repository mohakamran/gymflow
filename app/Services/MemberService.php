<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AccountInvitationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberService
{
    public function __construct(protected SequenceGenerator $sequences, protected PlanLimits $limits) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Tenant $tenant, array $data, ?UploadedFile $photo = null): Member
    {
        $this->limits->ensureCanAddMember($tenant);

        return DB::transaction(function () use ($tenant, $data, $photo): Member {
            $member = new Member($data);
            $member->tenant_id = $tenant->id;
            $member->member_code = $this->sequences->nextMemberCode($tenant);
            $member->joined_on ??= tenant_today();
            $member->save();

            if ($photo) {
                $this->storePhoto($member, $photo);
            }

            return $member;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Member $member, array $data, ?UploadedFile $photo = null, bool $removePhoto = false): Member
    {
        $member->fill($data)->save();

        if ($removePhoto && $member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
            $member->forceFill(['photo_path' => null])->save();
        }

        if ($photo) {
            $this->storePhoto($member, $photo);
        }

        if ($member->user && $member->wasChanged(['first_name', 'last_name'])) {
            $member->user->update(['name' => $member->full_name]);
        }

        return $member;
    }

    public function storePhoto(Member $member, UploadedFile $photo): void
    {
        $old = $member->photo_path;
        $member->forceFill(['photo_path' => $photo->store('tenants/'.$member->tenant_id.'/members', 'public')])->save();

        if ($old) {
            Storage::disk('public')->delete($old);
        }
    }

    /**
     * Give a member a login for the member portal and email them a link to set a password.
     */
    public function invitePortalAccess(Member $member): User
    {
        if ($member->user_id) {
            throw new BusinessRuleException('This member already has portal access.');
        }

        if (! $member->email) {
            throw new BusinessRuleException('Add an email address to the member before inviting them.');
        }

        if (User::withoutTenancy()->withTrashed()->where('email', $member->email)->exists()) {
            throw new BusinessRuleException('Another account already uses this email address.');
        }

        $user = DB::transaction(function () use ($member): User {
            $user = new User(['name' => $member->full_name, 'email' => $member->email, 'phone' => $member->phone, 'password' => Str::password(32)]);
            $user->tenant_id = $member->tenant_id;
            $user->email_verified_at = now();
            $user->save();
            $user->assignRole(Role::Member->value);

            $member->user()->associate($user)->save();

            return $user;
        });

        $user->notify(new AccountInvitationNotification(Tenant::findOrFail($member->tenant_id), 'member'));

        return $user;
    }

    public function revokePortalAccess(Member $member): void
    {
        $user = $member->user;

        if ($user === null) {
            return;
        }

        DB::transaction(function () use ($member, $user): void {
            $member->user()->dissociate()->save();
            $user->forceFill(['is_active' => false])->save();
            $user->delete();
        });
    }
}
