<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Builds the sidebar for the signed-in user. Items are hidden when the user lacks the
 * permission or when the module's route is not registered yet, so new modules appear
 * automatically once their routes exist.
 */
class Navigation
{
    /**
     * @return list<array{heading: string|null, items: list<array{label: string, route: string, icon: string, active: string}>}>
     */
    public static function for(User $user): array
    {
        $sections = $user->isSuperAdmin() ? self::platform() : self::workspace();

        return collect($sections)
            ->map(function (array $section) use ($user): array {
                $section['items'] = collect($section['items'])
                    ->filter(fn (array $item): bool => Route::has($item['route']) && ($item['permission'] === null || $user->can($item['permission'])))
                    ->map(fn (array $item): array => [
                        'label' => $item['label'],
                        'route' => $item['route'],
                        'icon' => $item['icon'],
                        'active' => $item['active'] ?? $item['route'],
                    ])
                    ->values()
                    ->all();

                return $section;
            })
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return list<array{heading: string|null, items: list<array<string, mixed>>}>
     */
    protected static function workspace(): array
    {
        return [
            ['heading' => null, 'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'permission' => Permission::DashboardView],
                ['label' => 'Overview', 'route' => 'portal.dashboard', 'icon' => 'home', 'permission' => Permission::PortalAccess],
                ['label' => 'My membership', 'route' => 'portal.membership', 'icon' => 'card', 'permission' => Permission::PortalAccess],
                ['label' => 'Book classes', 'route' => 'portal.classes', 'icon' => 'calendar', 'permission' => Permission::PortalAccess],
                ['label' => 'Workouts', 'route' => 'portal.workouts', 'icon' => 'bolt', 'permission' => Permission::PortalAccess, 'active' => 'portal.workouts*'],
                ['label' => 'Progress', 'route' => 'portal.progress', 'icon' => 'chart', 'permission' => Permission::PortalAccess],
                ['label' => 'Payments & invoices', 'route' => 'portal.billing', 'icon' => 'clipboard', 'permission' => Permission::PortalAccess],
                ['label' => 'Attendance', 'route' => 'portal.attendance', 'icon' => 'qr', 'permission' => Permission::PortalAccess],
                ['label' => 'Announcements', 'route' => 'portal.announcements', 'icon' => 'megaphone', 'permission' => Permission::PortalAccess],
            ]],
            ['heading' => 'Front desk', 'items' => [
                ['label' => 'Check-in', 'route' => 'attendance.index', 'icon' => 'qr', 'permission' => Permission::AttendanceManage, 'active' => 'attendance.*'],
                ['label' => 'Members', 'route' => 'members.index', 'icon' => 'users', 'permission' => Permission::MembersView, 'active' => 'members.*'],
                ['label' => 'Memberships', 'route' => 'memberships.index', 'icon' => 'card', 'permission' => Permission::MembershipsView, 'active' => 'memberships.*'],
                ['label' => 'Leads', 'route' => 'enquiries.index', 'icon' => 'mail', 'permission' => Permission::MembersManage, 'active' => 'enquiries.*'],
            ]],
            ['heading' => 'Programs', 'items' => [
                ['label' => 'Class schedule', 'route' => 'classes.index', 'icon' => 'calendar', 'permission' => Permission::ClassesView, 'active' => 'classes.*'],
                ['label' => 'Membership plans', 'route' => 'plans.index', 'icon' => 'sparkles', 'permission' => Permission::PlansManage, 'active' => 'plans.*'],
                ['label' => 'Announcements', 'route' => 'announcements.index', 'icon' => 'megaphone', 'permission' => Permission::AnnouncementsManage, 'active' => 'announcements.*'],
            ]],
            ['heading' => 'Finance', 'items' => [
                ['label' => 'Payments', 'route' => 'payments.index', 'icon' => 'card', 'permission' => Permission::PaymentsView, 'active' => 'payments.*'],
                ['label' => 'Invoices', 'route' => 'invoices.index', 'icon' => 'clipboard', 'permission' => Permission::InvoicesView, 'active' => 'invoices.*'],
                ['label' => 'Expenses', 'route' => 'expenses.index', 'icon' => 'receipt', 'permission' => Permission::ExpensesManage, 'active' => 'expenses.*'],
                ['label' => 'Reports', 'route' => 'reports.index', 'icon' => 'chart', 'permission' => Permission::ReportsView, 'active' => 'reports.*'],
            ]],
            ['heading' => 'Operations', 'items' => [
                ['label' => 'Team', 'route' => 'staff.index', 'icon' => 'shield', 'permission' => Permission::StaffManage, 'active' => 'staff.*'],
                ['label' => 'Equipment', 'route' => 'equipment.index', 'icon' => 'wrench', 'permission' => Permission::EquipmentManage, 'active' => 'equipment.*'],
                ['label' => 'Settings', 'route' => 'settings.profile.edit', 'icon' => 'cog', 'permission' => Permission::SettingsManage, 'active' => 'settings.*'],
                ['label' => 'Audit log', 'route' => 'audit-logs.index', 'icon' => 'clipboard', 'permission' => Permission::AuditView, 'active' => 'audit-logs.*'],
            ]],
        ];
    }

    /**
     * @return list<array{heading: string|null, items: list<array<string, mixed>>}>
     */
    protected static function platform(): array
    {
        return [
            ['heading' => 'Platform', 'items' => [
                ['label' => 'Overview', 'route' => 'admin.dashboard', 'icon' => 'chart', 'permission' => null],
                ['label' => 'Gyms', 'route' => 'admin.tenants.index', 'icon' => 'building', 'permission' => null, 'active' => 'admin.tenants.*'],
                ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users', 'permission' => null, 'active' => 'admin.users.*'],
                ['label' => 'SaaS plans', 'route' => 'admin.plans.index', 'icon' => 'sparkles', 'permission' => null, 'active' => 'admin.plans.*'],
                ['label' => 'Activity', 'route' => 'admin.activity.index', 'icon' => 'clipboard', 'permission' => null, 'active' => 'admin.activity.*'],
            ]],
        ];
    }
}
