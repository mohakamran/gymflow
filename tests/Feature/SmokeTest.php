<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Services\Reporting\ReportService;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seeds the full demo gym and renders every page for every role, so a broken view,
 * missing relation or N+1 lazy-load fails the build.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $gym;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->gym = Tenant::where('slug', 'iron-peak-fitness')->firstOrFail();
    }

    /**
     * @return list<string>
     */
    protected function workspaceUrls(): array
    {
        return app(TenantContext::class)->run($this->gym, function (): array {
            $member = Member::query()->whereNotNull('user_id')->firstOrFail();
            $session = ClassSession::query()->firstOrFail();
            $urls = [
                route('dashboard'), route('members.index'), route('members.index', ['membership' => 'expiring', 'search' => 'a']),
                route('members.create'), route('members.show', $member), route('members.edit', $member), route('members.card', $member),
                route('members.lookup', ['q' => 'ma']), route('members.workouts.create', $member),
                route('members.workouts.show', [$member, WorkoutPlan::query()->firstOrFail()]),
                route('plans.index'), route('plans.create'), route('plans.edit', MembershipPlan::query()->firstOrFail()),
                route('memberships.index'), route('memberships.index', ['state' => 'expiring']), route('memberships.index', ['state' => 'expired']),
                route('memberships.create', ['member' => $member->id]), route('memberships.renew', $member->memberships()->firstOrFail()),
                route('attendance.index'), route('attendance.history'), route('attendance.kiosk'),
                route('staff.index'), route('staff.create'), route('staff.edit', User::query()->role('trainer')->firstOrFail()),
                route('classes.index'), route('classes.index', ['week' => now()->addWeek()->toDateString()]), route('classes.sessions.create'),
                route('classes.sessions.show', $session), route('classes.types.index'), route('classes.types.create'), route('classes.types.edit', $session->gym_class_id),
                route('equipment.index'), route('equipment.index', ['maintenance' => 'due']), route('equipment.create'),
                route('equipment.show', Equipment::query()->firstOrFail()), route('equipment.edit', Equipment::query()->firstOrFail()),
                route('payments.index'), route('payments.create'), route('payments.create', ['member' => $member->id]), route('payments.show', Payment::query()->firstOrFail()),
                route('invoices.index'), route('invoices.index', ['status' => 'overdue']), route('invoices.create'),
                route('invoices.show', Invoice::query()->firstOrFail()), route('invoices.print', Invoice::query()->firstOrFail()),
                route('expenses.index'), route('expenses.create'), route('expenses.edit', Expense::query()->firstOrFail()),
                route('reports.index'), route('announcements.index'), route('announcements.create'), route('enquiries.index'),
                route('settings.profile.edit'), route('settings.branding.edit'), route('settings.hours.edit'), route('settings.localization.edit'),
                route('settings.notifications.edit'), route('settings.billing.edit'), route('audit-logs.index'), route('notifications.index'), route('profile.edit'),
            ];

            foreach (array_keys(ReportService::TYPES) as $type) {
                $urls[] = route('reports.show', $type);
                $urls[] = route('reports.show', [$type, 'from' => now()->subYear()->toDateString(), 'to' => now()->toDateString()]);
            }

            return $urls;
        });
    }

    public function test_every_workspace_page_renders_for_the_owner(): void
    {
        $owner = User::where('email', 'owner@gymflow.test')->firstOrFail();

        foreach ($this->workspaceUrls() as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }
    }

    public function test_staff_and_trainer_pages_never_error(): void
    {
        foreach (['staff@gymflow.test', 'trainer@gymflow.test'] as $email) {
            $user = User::where('email', $email)->firstOrFail();

            foreach ($this->workspaceUrls() as $url) {
                $status = $this->actingAs($user)->get($url)->status();
                $this->assertContains($status, [200, 302, 403], "$email got $status on $url");
            }
        }
    }

    public function test_exports_work(): void
    {
        $owner = User::where('email', 'owner@gymflow.test')->firstOrFail();

        foreach (array_keys(ReportService::TYPES) as $type) {
            $this->actingAs($owner)->get(route('reports.export', [$type, 'csv']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
            $this->actingAs($owner)->get(route('reports.export', [$type, 'print']))->assertOk();
        }

        $this->actingAs($owner)->get(route('reports.export', ['revenue', 'pdf']))->assertOk()->assertHeader('content-type', 'application/pdf');

        $invoice = app(TenantContext::class)->run($this->gym, fn () => Invoice::query()->firstOrFail());
        $this->actingAs($owner)->get(route('invoices.pdf', $invoice))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_member_portal_pages_render(): void
    {
        $user = User::where('email', 'member@gymflow.test')->firstOrFail();
        $invoice = app(TenantContext::class)->run($this->gym, fn () => $user->member->invoices()->firstOrFail());

        foreach (['portal.dashboard', 'portal.membership', 'portal.billing', 'portal.attendance', 'portal.classes', 'portal.workouts', 'portal.progress', 'portal.announcements', 'portal.notifications'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }

        $this->actingAs($user)->get(route('portal.invoices.pdf', $invoice))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('members.index'))->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::where('email', 'admin@gymflow.test')->firstOrFail();

        foreach ([route('admin.dashboard'), route('admin.tenants.index'), route('admin.tenants.show', $this->gym), route('admin.users.index'), route('admin.plans.index'), route('admin.activity.index'), route('admin.activity.index', ['tenant' => $this->gym->id])] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_public_pages_render(): void
    {
        $this->get(route('public.gym', 'iron-peak-fitness'))->assertOk()->assertSee('Iron Peak Fitness')->assertSee('application/ld+json', false);
        $this->get(route('sitemap'))->assertOk()->assertSee('iron-peak-fitness');
        $this->get(route('public.gym', 'summit-strength-club'))->assertNotFound();
    }
}
