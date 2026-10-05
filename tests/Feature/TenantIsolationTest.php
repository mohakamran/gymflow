<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queries_are_scoped_to_the_active_tenant(): void
    {
        $gymA = Tenant::factory()->create();
        $gymB = Tenant::factory()->create();
        $userA = $this->gymUser(tenant: $gymA);
        $userB = $this->gymUser(tenant: $gymB);

        app(TenantContext::class)->run($gymA, function () use ($userA, $userB): void {
            $this->assertSame([$userA->id], User::pluck('id')->all());
            $this->assertNull(User::find($userB->id));
        });
    }

    public function test_new_records_are_stamped_with_the_active_tenant(): void
    {
        $gym = Tenant::factory()->create();

        $user = app(TenantContext::class)->run($gym, fn () => User::factory()->create());

        $this->assertSame($gym->id, $user->tenant_id);
    }

    public function test_records_cannot_be_moved_to_another_tenant(): void
    {
        $user = $this->gymUser();
        $other = Tenant::factory()->create();

        $this->expectException(\LogicException::class);

        $user->tenant_id = $other->id;
        $user->save();
    }

    public function test_dashboard_activity_only_shows_own_gym(): void
    {
        $owner = $this->gymUser();
        $otherOwner = $this->gymUser();
        AuditLog::withoutTenancy()->create(['tenant_id' => $otherOwner->tenant_id, 'event' => 'secret.other_gym_event']);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('secret.other_gym_event');
    }

    public function test_audit_log_only_lists_own_gym_entries_and_users(): void
    {
        $owner = $this->gymUser();
        $otherOwner = $this->gymUser();
        AuditLog::withoutTenancy()->create(['tenant_id' => $otherOwner->tenant_id, 'user_id' => $otherOwner->id, 'event' => 'secret.other_gym_event']);

        $this->actingAs($owner)->get(route('audit-logs.index'))
            ->assertOk()
            ->assertDontSee('secret.other_gym_event')
            ->assertDontSee($otherOwner->email)
            ->assertDontSee('>'.$otherOwner->name.'<', false);
    }

    public function test_settings_update_only_affects_own_gym(): void
    {
        $owner = $this->gymUser();
        $otherGym = Tenant::factory()->create(['name' => 'Untouched Gym']);

        $this->actingAs($owner)->put(route('settings.profile.update'), ['name' => 'Renamed Gym'])->assertRedirect();

        $this->assertSame('Renamed Gym', $owner->tenant->fresh()->name);
        $this->assertSame('Untouched Gym', $otherGym->fresh()->name);
    }

    public function test_users_of_a_suspended_gym_are_signed_out(): void
    {
        $gym = Tenant::factory()->suspended()->create();
        $owner = $this->gymUser(tenant: $gym);

        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_deactivated_users_are_signed_out(): void
    {
        $gym = Tenant::factory()->create();
        $user = User::factory()->forTenant($gym)->inactive()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
