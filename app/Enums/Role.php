<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Owner = 'owner';
    case Staff = 'staff';
    case Trainer = 'trainer';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Owner => 'Gym Owner',
            self::Staff => 'Staff / Reception',
            self::Trainer => 'Trainer',
            self::Member => 'Member',
        };
    }

    /**
     * Roles a gym owner may assign to people inside their gym.
     *
     * @return list<self>
     */
    public static function tenantAssignable(): array
    {
        return [self::Owner, self::Staff, self::Trainer, self::Member];
    }

    /**
     * Permissions granted to the role. The super admin bypasses checks via Gate::before.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [],
            self::Owner => Permission::all(),
            self::Staff => [
                Permission::DashboardView,
                Permission::MembersView, Permission::MembersManage,
                Permission::MembershipsView, Permission::MembershipsManage,
                Permission::AttendanceManage,
                Permission::PaymentsView, Permission::PaymentsManage,
                Permission::InvoicesView, Permission::InvoicesManage,
                Permission::ClassesView,
                Permission::AnnouncementsManage,
            ],
            self::Trainer => [
                Permission::DashboardView,
                Permission::MembersView,
                Permission::ClassesView, Permission::ClassesManage,
                Permission::WorkoutsManage,
            ],
            self::Member => [
                Permission::PortalAccess,
            ],
        };
    }
}
