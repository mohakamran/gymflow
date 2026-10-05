<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Tenant;
use App\Services\Reporting\DashboardMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetrics $metrics): View
    {
        $tenant = tenant();
        $user = $request->user();
        $isTrainerOnly = $user->hasRole(Role::Trainer->value) && ! $user->can(Permission::MembersManage);

        return view('dashboard', [
            'tenant' => $tenant,
            'isTrainerOnly' => $isTrainerOnly,
            'setupSteps' => $this->setupSteps($tenant),
            'kpis' => $metrics->kpis(),
            'performance' => $user->can(Permission::ReportsView) ? $metrics->monthlyPerformance(6) : null,
            'attendanceTrend' => $metrics->attendanceTrend(30),
            'growth' => $user->can(Permission::ReportsView) ? $metrics->memberGrowth(12) : null,
            'byPlan' => $metrics->membershipsByPlan(),
            'expensesByCategory' => $user->can(Permission::ExpensesManage) ? $metrics->expensesByCategory() : null,
            'upcomingClasses' => $metrics->upcomingClasses($isTrainerOnly ? $user : null),
            'expiring' => $user->can(Permission::MembershipsView) ? $metrics->expiringMemberships() : collect(),
            'myMembers' => $isTrainerOnly ? Member::query()->where('trainer_id', $user->id)->with('currentMembership.plan')->orderBy('first_name')->limit(8)->get() : collect(),
            'announcements' => Announcement::query()->published()->latest('published_at')->limit(3)->get(),
            'recentActivity' => $user->can(Permission::AuditView) ? AuditLog::query()->with('user')->latest('created_at')->limit(6)->get() : collect(),
        ]);
    }

    /**
     * @return list<array{label: string, done: bool, route: string|null}>
     */
    protected function setupSteps(Tenant $tenant): array
    {
        return [
            ['label' => 'Add your contact details & address', 'done' => filled($tenant->phone) && filled($tenant->address_line), 'route' => 'settings.profile.edit'],
            ['label' => 'Upload your logo & pick brand colors', 'done' => filled($tenant->logo_path), 'route' => 'settings.branding.edit'],
            ['label' => 'Set your business hours', 'done' => (bool) $tenant->setting('onboarding.hours_confirmed'), 'route' => 'settings.hours.edit'],
            ['label' => 'Create your membership plans', 'done' => MembershipPlan::query()->exists(), 'route' => 'plans.create'],
            ['label' => 'Add your first member', 'done' => Member::query()->exists(), 'route' => 'members.create'],
        ];
    }
}
