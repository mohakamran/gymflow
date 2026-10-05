<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\PlanChangeRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every URL that takes a record ID must 404 when the record belongs to another gym.
 */
class TenantBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_tenant_urls_are_not_found(): void
    {
        $gymA = Tenant::factory()->create();
        $gymB = Tenant::factory()->create();
        $ownerA = $this->gymUser(Role::Owner, $gymA);
        $trainerB = $this->gymUser(Role::Trainer, $gymB);

        [$memberB, $invoiceB, $planB] = $this->inTenant($gymB, function () use ($gymB) {
            $member = Member::factory()->create(['tenant_id' => $gymB->id]);
            $plan = MembershipPlan::factory()->create();
            $sale = app(MembershipService::class)->sell($member, $plan);

            return [$member, $sale['invoice'], $plan];
        });

        foreach ([
            route('members.show', $memberB), route('members.edit', $memberB), route('members.card', $memberB),
            route('invoices.show', $invoiceB), route('invoices.pdf', $invoiceB), route('plans.edit', $planB), route('staff.edit', $trainerB),
        ] as $url) {
            $this->actingAs($ownerA)->get($url)->assertNotFound();
        }

        $this->actingAs($ownerA)->delete(route('members.destroy', $memberB))->assertNotFound();
        $this->actingAs($ownerA)->post(route('payments.store'), ['member_id' => $memberB->id, 'invoice_id' => $invoiceB->id, 'amount' => 1, 'method' => 'cash'])->assertSessionHasErrors(['member_id', 'invoice_id']);
        $this->actingAs($ownerA)->post(route('attendance.store'), ['code' => $memberB->member_code])->assertSessionHas('toast', fn ($t) => $t['type'] === 'error');

        $this->assertNull($memberB->fresh()->deleted_at);
        $this->assertSame(0, $this->inTenant($gymB, fn () => Payment::count()));
    }

    public function test_member_lookup_and_reports_only_include_own_gym(): void
    {
        $gymA = Tenant::factory()->create();
        $gymB = Tenant::factory()->create();
        $ownerA = $this->gymUser(Role::Owner, $gymA);
        $this->inTenant($gymB, fn () => Member::factory()->create(['tenant_id' => $gymB->id, 'first_name' => 'Zelda', 'last_name' => 'Secret']));

        $this->actingAs($ownerA)->getJson(route('members.lookup', ['q' => 'Zelda']))->assertOk()->assertExactJson([]);
        $this->actingAs($ownerA)->get(route('reports.export', ['expired', 'csv']))->assertOk()->assertDontSee('Zelda');
    }

    public function test_portal_member_cannot_download_another_members_invoice(): void
    {
        $gym = Tenant::factory()->create();
        $user = $this->gymUser(Role::Member, $gym);

        [$own, $other] = $this->inTenant($gym, function () use ($gym, $user) {
            $plan = MembershipPlan::factory()->create();
            $mine = Member::factory()->create(['tenant_id' => $gym->id, 'user_id' => $user->id]);
            $theirs = Member::factory()->create(['tenant_id' => $gym->id]);

            return [app(MembershipService::class)->sell($mine, $plan)['invoice'], app(MembershipService::class)->sell($theirs, $plan)['invoice']];
        });

        $this->actingAs($user)->get(route('portal.invoices.pdf', $own))->assertOk();
        $this->actingAs($user)->get(route('portal.invoices.pdf', $other))->assertNotFound();
    }

    public function test_csv_export_neutralises_formula_injection(): void
    {
        $gym = Tenant::factory()->create();
        $owner = $this->gymUser(Role::Owner, $gym);
        $this->inTenant($gym, fn () => Expense::create(['category' => 'other', 'title' => '=HYPERLINK("http://evil")', 'amount' => 5, 'spent_on' => now()->toDateString()]));

        $csv = $this->actingAs($owner)->get(route('reports.export', ['expenses', 'csv']))->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_public_enquiry_and_admin_plan_approval(): void
    {
        $gym = Tenant::factory()->create(['public_profile_enabled' => true]);
        $owner = $this->gymUser(Role::Owner, $gym);

        $this->post(route('public.enquire', $gym->slug), ['name' => 'Lead', 'email' => 'lead@example.com'])->assertRedirect();
        $this->post(route('public.enquire', $gym->slug), ['name' => 'Bot', 'email' => 'bot@example.com', 'website' => 'spam'])->assertSessionHasErrors('website');
        $this->assertSame(1, $this->inTenant($gym, fn () => Enquiry::count()));

        $this->actingAs($owner)->post(route('settings.billing.request'), ['plan' => 'business'])->assertRedirect();
        $request = PlanChangeRequest::withoutTenancy()->sole();

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->patch(route('admin.plans.resolve', $request->id), ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('business', $gym->fresh()->subscription_plan->value);
    }

    public function test_member_search_is_case_insensitive_and_matches_full_name(): void
    {
        $gym = Tenant::factory()->create();
        $owner = $this->gymUser(Role::Owner, $gym);
        $this->inTenant($gym, fn () => Member::factory()->create(['tenant_id' => $gym->id, 'first_name' => 'Maya', 'last_name' => 'Johnson']));

        $this->actingAs($owner)->getJson(route('members.lookup', ['q' => 'maya']))->assertOk()->assertJsonCount(1);
        $this->actingAs($owner)->getJson(route('members.lookup', ['q' => 'MAYA JOHN']))->assertOk()->assertJsonCount(1);
        $this->actingAs($owner)->getJson(route('members.lookup', ['q' => 'nobody']))->assertOk()->assertJsonCount(0);
    }
}
