<?php

namespace Tests\Feature;

use App\Enums\DurationUnit;
use App\Enums\InvoiceStatus;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MembershipRenewedNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MembershipBillingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $gym;

    protected User $staff;

    protected Member $member;

    protected MembershipPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->gym = Tenant::factory()->create(['settings' => ['tax' => ['enabled' => true, 'rate' => 10, 'label' => 'VAT'], 'invoice' => ['prefix' => 'IPF-']]]);
        $this->staff = $this->gymUser(Role::Staff, $this->gym);
        [$this->member, $this->plan] = $this->inTenant($this->gym, fn () => [
            Member::factory()->create(['tenant_id' => $this->gym->id]),
            MembershipPlan::factory()->create(['price' => 100, 'signup_fee' => 20, 'duration_value' => 1, 'duration_unit' => DurationUnit::Month]),
        ]);
    }

    public function test_selling_a_membership_creates_invoice_with_tax_fee_and_payment(): void
    {
        $this->actingAs($this->staff)->post(route('memberships.store'), [
            'member_id' => $this->member->id,
            'membership_plan_id' => $this->plan->id,
            'starts_on' => now()->toDateString(),
            'discount_amount' => 10,
            'payment_amount' => 50,
            'payment_method' => 'cash',
        ])->assertRedirect(route('members.show', $this->member))->assertSessionHas('toast');

        $this->inTenant($this->gym, function (): void {
            $membership = Membership::sole();
            $this->assertSame(MembershipStatus::Active, $membership->status);
            $this->assertSame(now()->addMonthNoOverflow()->subDay()->toDateString(), $membership->ends_on->toDateString());

            $invoice = Invoice::sole();
            // (100 - 10 discount) + 20 joining fee = 110, +10% VAT = 121
            $this->assertSame('IPF-00001', $invoice->number);
            $this->assertEquals(120, (float) $invoice->subtotal);
            $this->assertEquals(10, (float) $invoice->discount_total);
            $this->assertEquals(11, (float) $invoice->tax_total);
            $this->assertEquals(121, (float) $invoice->total);
            $this->assertEquals(50, (float) $invoice->amount_paid);
            $this->assertSame(InvoiceStatus::Partial, $invoice->status);
            $this->assertCount(2, $invoice->items);
        });

        Notification::assertSentTo($this->member, PaymentReceivedNotification::class);
        Notification::assertSentTo($this->member, MembershipRenewedNotification::class);
    }

    public function test_invoice_numbers_are_sequential_per_gym(): void
    {
        $otherGym = Tenant::factory()->create();
        [$otherMember, $otherPlan] = $this->inTenant($otherGym, fn () => [Member::factory()->create(['tenant_id' => $otherGym->id]), MembershipPlan::factory()->create()]);

        $service = app(MembershipService::class);
        $this->inTenant($this->gym, fn () => $service->sell($this->member, $this->plan));
        $this->inTenant($this->gym, fn () => $service->sell($this->member, $this->plan));
        $other = $this->inTenant($otherGym, fn () => $service->sell($otherMember, $otherPlan));

        $this->inTenant($this->gym, fn () => $this->assertSame(['IPF-00001', 'IPF-00002'], Invoice::orderBy('id')->pluck('number')->all()));
        $this->assertSame('INV-00001', $other['invoice']->number);
    }

    public function test_renewal_starts_after_current_membership_and_skips_joining_fee(): void
    {
        $service = app(MembershipService::class);
        $first = $this->inTenant($this->gym, fn () => $service->sell($this->member, $this->plan));
        $renewal = $this->inTenant($this->gym, fn () => $service->renew($first['membership']));

        $this->assertSame($first['membership']->ends_on->addDay()->toDateString(), $renewal['membership']->starts_on->toDateString());
        $this->assertSame(MembershipStatus::Pending, $renewal['membership']->status);
        $this->assertCount(1, $renewal['invoice']->items);
        $this->assertSame($first['membership']->id, $renewal['membership']->renewed_from_id);
    }

    public function test_freezing_extends_end_date_on_resume(): void
    {
        $service = app(MembershipService::class);
        $membership = $this->inTenant($this->gym, fn () => $service->sell($this->member, $this->plan)['membership']);
        $originalEnd = $membership->ends_on->copy();

        $this->actingAs($this->staff)->post(route('memberships.suspend', $membership))->assertRedirect();
        $this->travel(5)->days();
        $this->actingAs($this->staff)->post(route('memberships.resume', $membership))->assertRedirect();

        $this->assertSame($originalEnd->addDays(5)->toDateString(), $membership->fresh()->ends_on->toDateString());
        $this->assertSame(MembershipStatus::Active, $membership->fresh()->status);
    }

    public function test_payments_and_refunds_keep_invoice_status_in_sync(): void
    {
        $invoice = $this->inTenant($this->gym, fn () => app(MembershipService::class)->sell($this->member, $this->plan)['invoice']);

        $this->actingAs($this->staff)->post(route('payments.store'), ['member_id' => $this->member->id, 'invoice_id' => $invoice->id, 'amount' => 200, 'method' => 'card'])
            ->assertSessionHas('toast', fn ($toast) => $toast['type'] === 'error');

        $this->actingAs($this->staff)->post(route('payments.store'), ['member_id' => $this->member->id, 'invoice_id' => $invoice->id, 'amount' => $invoice->total, 'method' => 'card'])->assertRedirect();
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);

        $payment = $this->inTenant($this->gym, fn () => Payment::sole());
        $owner = $this->gymUser(Role::Owner, $this->gym);
        $this->actingAs($owner)->post(route('payments.refund', $payment), ['amount' => 30])->assertRedirect();

        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->fresh()->status);
        $this->assertSame(InvoiceStatus::Partial, $invoice->fresh()->status);

        $this->actingAs($owner)->post(route('payments.refund', $payment), ['amount' => $payment->fresh()->refundableAmount()])->assertRedirect();
        $this->assertSame(PaymentStatus::Refunded, $payment->fresh()->status);
        $this->assertSame(InvoiceStatus::Refunded, $invoice->fresh()->status);
    }

    public function test_custom_invoice_and_void(): void
    {
        $this->actingAs($this->staff)->post(route('invoices.store'), [
            'member_id' => $this->member->id,
            'issued_on' => now()->toDateString(),
            'due_on' => now()->addWeek()->toDateString(),
            'items' => [
                ['description' => 'PT session', 'quantity' => 2, 'unit_price' => 40, 'discount' => 0, 'taxable' => '1'],
                ['description' => 'Water bottle', 'quantity' => 1, 'unit_price' => 15, 'taxable' => '0'],
                ['description' => '', 'quantity' => 1, 'unit_price' => 0],
            ],
        ])->assertRedirect();

        $invoice = $this->inTenant($this->gym, fn () => Invoice::sole());
        $this->assertEquals(103, (float) $invoice->total); // 80 + 8 tax + 15
        $this->assertCount(2, $invoice->items);

        $this->actingAs($this->staff)->post(route('invoices.void', $invoice))->assertRedirect();
        $this->assertSame(InvoiceStatus::Void, $invoice->fresh()->status);
    }

    public function test_scheduler_expires_and_activates_memberships(): void
    {
        $this->inTenant($this->gym, function (): void {
            Membership::factory()->create(['tenant_id' => $this->gym->id, 'member_id' => $this->member->id, 'membership_plan_id' => $this->plan->id, 'starts_on' => now()->subMonths(2), 'ends_on' => now()->subDay(), 'status' => MembershipStatus::Active]);
            Membership::factory()->create(['tenant_id' => $this->gym->id, 'member_id' => $this->member->id, 'membership_plan_id' => $this->plan->id, 'starts_on' => now(), 'ends_on' => now()->addMonth(), 'status' => MembershipStatus::Pending]);
        });

        $this->artisan('gym:memberships:refresh')->assertSuccessful();

        $this->inTenant($this->gym, fn () => $this->assertSame(
            [MembershipStatus::Expired, MembershipStatus::Active],
            Membership::orderBy('id')->get()->pluck('status')->all(),
        ));
    }

    public function test_plan_member_limit_is_enforced(): void
    {
        $this->gym->forceFill(['subscription_plan' => 'starter'])->save();
        $this->inTenant($this->gym, fn () => Member::factory()->count(149)->create(['tenant_id' => $this->gym->id]));

        $owner = $this->gymUser(Role::Owner, $this->gym);
        $this->actingAs($owner)->post(route('members.store'), ['first_name' => 'Over', 'last_name' => 'Limit', 'joined_on' => now()->toDateString(), 'status' => 'active'])
            ->assertSessionHas('toast', fn ($toast) => str_contains($toast['message'], 'Upgrade'));

        $this->inTenant($this->gym, fn () => $this->assertSame(150, Member::count()));
    }
}
