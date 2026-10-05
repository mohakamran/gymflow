<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\MembershipStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Equipment;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AccountInvitationNotification;
use App\Notifications\ClassReminderNotification;
use App\Notifications\MaintenanceDueNotification;
use App\Notifications\MembershipExpiringNotification;
use App\Services\ClassScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $gym;

    protected User $owner;

    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->gym = Tenant::factory()->create();
        $this->owner = $this->gymUser(Role::Owner, $this->gym);
        $this->member = $this->inTenant($this->gym, function (): Member {
            $member = Member::factory()->create(['tenant_id' => $this->gym->id]);
            $plan = MembershipPlan::factory()->create(['class_limit_per_week' => 1]);
            Membership::factory()->create(['tenant_id' => $this->gym->id, 'member_id' => $member->id, 'membership_plan_id' => $plan->id, 'starts_on' => now()->subDays(3), 'ends_on' => now()->addDays(3)]);

            return $member;
        });
    }

    public function test_check_in_by_qr_code_then_toggle_out(): void
    {
        $this->actingAs($this->owner)->postJson(route('attendance.store'), ['code' => $this->member->checkInPayload(), 'toggle' => true])
            ->assertOk()->assertJsonPath('action', 'in');
        $this->actingAs($this->owner)->postJson(route('attendance.store'), ['code' => $this->member->member_code, 'toggle' => true])
            ->assertOk()->assertJsonPath('action', 'out');

        $visit = $this->inTenant($this->gym, fn () => Attendance::sole());
        $this->assertNotNull($visit->checked_out_at);
    }

    public function test_check_in_rejected_without_active_membership(): void
    {
        $this->inTenant($this->gym, fn () => Membership::query()->update(['status' => MembershipStatus::Expired->value]));

        $this->actingAs($this->owner)->postJson(route('attendance.store'), ['member_id' => $this->member->id])
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'no active membership'));
    }

    public function test_class_booking_respects_capacity_and_weekly_limit(): void
    {
        [$session, $second] = $this->inTenant($this->gym, function (): array {
            $class = GymClass::create(['name' => 'Spin', 'capacity' => 1, 'duration_minutes' => 45, 'color' => '#000000', 'allow_member_booking' => true]);
            $sessions = app(ClassScheduleService::class)->schedule($class, ['date' => now()->addDay()->toDateString(), 'start_time' => '18:00', 'repeat_days' => [now()->addDay()->dayOfWeekIso, now()->addDays(2)->dayOfWeekIso], 'repeat_until' => now()->addDays(2)->toDateString()]);

            return [$sessions[0], $sessions[1]];
        });

        $this->actingAs($this->owner)->post(route('classes.bookings.store', $session), ['member_id' => $this->member->id])->assertSessionHas('toast', fn ($t) => $t['type'] === 'success');
        $this->assertSame(1, $this->inTenant($this->gym, fn () => ClassBooking::count()));

        // Full class.
        $other = $this->inTenant($this->gym, function () {
            $m = Member::factory()->create(['tenant_id' => $this->gym->id]);
            Membership::factory()->create(['tenant_id' => $this->gym->id, 'member_id' => $m->id, 'membership_plan_id' => MembershipPlan::first()->id, 'starts_on' => now()->subDay(), 'ends_on' => now()->addWeek()]);

            return $m;
        });
        $this->actingAs($this->owner)->post(route('classes.bookings.store', $session), ['member_id' => $other->id])->assertSessionHas('toast', fn ($t) => str_contains($t['message'], 'full'));

        // Weekly limit of 1 (only applies if both sessions fall in the same ISO week).
        if ($session->starts_at->isSameWeek($second->starts_at)) {
            $this->actingAs($this->owner)->post(route('classes.bookings.store', $second), ['member_id' => $this->member->id])->assertSessionHas('toast', fn ($t) => str_contains($t['message'], 'per week'));
        }
    }

    public function test_cancelling_a_session_releases_bookings(): void
    {
        $session = $this->inTenant($this->gym, function () {
            $class = GymClass::create(['name' => 'Yoga', 'capacity' => 5, 'duration_minutes' => 60, 'color' => '#000000']);
            $session = app(ClassScheduleService::class)->schedule($class, ['date' => now()->addDay()->toDateString(), 'start_time' => '07:00'])->first();
            app(ClassScheduleService::class)->book($session, $this->member, true);

            return $session;
        });

        $this->actingAs($this->owner)->post(route('classes.sessions.cancel', $session))->assertRedirect();
        $this->assertSame(BookingStatus::Cancelled, $this->inTenant($this->gym, fn () => ClassBooking::sole()->status));
    }

    public function test_inviting_team_member_and_last_owner_protection(): void
    {
        $this->actingAs($this->owner)->post(route('staff.store'), ['name' => 'New Coach', 'email' => 'coach@example.com', 'role' => 'trainer', 'is_public' => '1'])
            ->assertRedirect(route('staff.index'));

        $coach = User::where('email', 'coach@example.com')->firstOrFail();
        $this->assertTrue($coach->hasRole('trainer'));
        $this->assertSame($this->gym->id, $coach->tenant_id);
        Notification::assertSentTo($coach, AccountInvitationNotification::class);

        $this->actingAs($this->owner)->put(route('staff.update', $this->owner), ['name' => $this->owner->name, 'role' => 'staff'])
            ->assertSessionHas('toast', fn ($t) => $t['type'] === 'error');
        $this->assertTrue($this->owner->fresh()->hasRole('owner'));
    }

    public function test_trainer_sees_only_assigned_members(): void
    {
        $trainer = $this->gymUser(Role::Trainer, $this->gym);
        $assigned = $this->inTenant($this->gym, fn () => Member::factory()->create(['tenant_id' => $this->gym->id, 'trainer_id' => $trainer->id, 'first_name' => 'Assigned']));

        $this->actingAs($trainer)->get(route('members.index'))->assertOk()->assertSee('Assigned')->assertDontSee($this->member->first_name.' '.$this->member->last_name);
        $this->actingAs($trainer)->get(route('members.show', $assigned))->assertOk();
        $this->actingAs($trainer)->get(route('members.show', $this->member))->assertForbidden();

        $this->actingAs($trainer)->post(route('members.progress.store', $assigned), ['recorded_on' => now()->toDateString(), 'weight_kg' => 80])->assertRedirect();
        $this->actingAs($trainer)->post(route('members.notes.store', $this->member), ['body' => 'x'])->assertForbidden();
    }

    public function test_reminder_commands_send_notifications_once(): void
    {
        $this->inTenant($this->gym, function (): void {
            $class = GymClass::create(['name' => 'Spin', 'capacity' => 5, 'duration_minutes' => 45, 'color' => '#000000']);
            $session = ClassSession::create(['gym_class_id' => $class->id, 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'capacity' => 5, 'status' => 'scheduled']);
            ClassBooking::create(['class_session_id' => $session->id, 'member_id' => $this->member->id, 'status' => 'booked']);
            Equipment::create(['name' => 'Treadmill', 'category' => 'cardio', 'quantity' => 1, 'condition' => 'good', 'status' => 'active', 'next_maintenance_on' => now()->subDay()]);
        });

        foreach (['gym:reminders:expiring', 'gym:reminders:classes', 'gym:reminders:maintenance'] as $command) {
            $this->artisan($command)->assertSuccessful();
            $this->artisan($command)->assertSuccessful();
        }

        Notification::assertSentToTimes($this->member, MembershipExpiringNotification::class, 1);
        Notification::assertSentToTimes($this->member, ClassReminderNotification::class, 1);
        Notification::assertSentToTimes($this->owner, MaintenanceDueNotification::class, 1);
    }

    public function test_notification_preferences_disable_emails(): void
    {
        $this->actingAs($this->owner)->put(route('settings.notifications.update'), ['expiry_reminder_days' => 7, 'membership_expiry' => '0'])->assertRedirect();

        $this->artisan('gym:reminders:expiring')->assertSuccessful();
        Notification::assertNotSentTo($this->member, MembershipExpiringNotification::class);
    }

    public function test_member_portal_invite_and_booking(): void
    {
        $this->actingAs($this->owner)->post(route('members.portal.invite', $this->member))->assertRedirect();
        $user = User::where('email', $this->member->email)->firstOrFail();
        $this->assertTrue($user->hasRole('member'));
        Notification::assertSentTo($user, AccountInvitationNotification::class);

        $session = $this->inTenant($this->gym, function () {
            $class = GymClass::create(['name' => 'Pilates', 'capacity' => 5, 'duration_minutes' => 50, 'color' => '#000000', 'allow_member_booking' => true]);

            return app(ClassScheduleService::class)->schedule($class, ['date' => now()->addDay()->toDateString(), 'start_time' => '09:00'])->first();
        });

        $this->actingAs($user)->post(route('portal.classes.book', $session))->assertRedirect();
        $booking = $this->inTenant($this->gym, fn () => ClassBooking::sole());
        $this->actingAs($user)->delete(route('portal.bookings.cancel', $booking))->assertRedirect();
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }
}
