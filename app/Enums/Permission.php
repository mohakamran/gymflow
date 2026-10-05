<?php

namespace App\Enums;

/**
 * Central list of permission names. Kept as constants so they can be used in
 * policies, middleware, Blade (@can) and the seeder without typos.
 */
final class Permission
{
    public const DashboardView = 'dashboard.view';

    public const MembersView = 'members.view';

    public const MembersManage = 'members.manage';

    public const MembershipsView = 'memberships.view';

    public const MembershipsManage = 'memberships.manage';

    public const PlansManage = 'plans.manage';

    public const AttendanceManage = 'attendance.manage';

    public const StaffManage = 'staff.manage';

    public const ClassesView = 'classes.view';

    public const ClassesManage = 'classes.manage';

    public const WorkoutsManage = 'workouts.manage';

    public const EquipmentManage = 'equipment.manage';

    public const PaymentsView = 'payments.view';

    public const PaymentsManage = 'payments.manage';

    public const InvoicesView = 'invoices.view';

    public const InvoicesManage = 'invoices.manage';

    public const ExpensesManage = 'expenses.manage';

    public const ReportsView = 'reports.view';

    public const SettingsManage = 'settings.manage';

    public const AuditView = 'audit.view';

    public const AnnouncementsManage = 'announcements.manage';

    public const BillingManage = 'billing.manage';

    public const PortalAccess = 'portal.access';

    /**
     * Every tenant-level permission, excluding the member portal.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_filter(
            (new \ReflectionClass(self::class))->getConstants(),
            fn (string $permission): bool => $permission !== self::PortalAccess,
        ));
    }

    /**
     * @return list<string>
     */
    public static function everything(): array
    {
        return array_values((new \ReflectionClass(self::class))->getConstants());
    }
}
