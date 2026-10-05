<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TenantStatus;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_gym_owner_can_register_a_new_gym(): void
    {
        Notification::fake();

        $response = $this->post(route('register'), [
            'gym_name' => 'Peak Performance',
            'name' => 'Jordan Lee',
            'email' => 'jordan@example.com',
            'currency' => 'GBP',
            'timezone' => 'Europe/London',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $tenant = Tenant::where('slug', 'peak-performance')->firstOrFail();
        $this->assertSame(TenantStatus::Trial, $tenant->status);
        $this->assertSame('GBP', $tenant->currency);

        $owner = User::where('email', 'jordan@example.com')->firstOrFail();
        $this->assertSame($tenant->id, $owner->tenant_id);
        $this->assertTrue($owner->hasRole(Role::Owner->value));
        Notification::assertSentTo($owner, VerifyEmail::class);
    }

    public function test_registration_generates_unique_slugs(): void
    {
        Tenant::factory()->create(['slug' => 'peak-performance']);

        $this->post(route('register'), [
            'gym_name' => 'Peak Performance', 'name' => 'A', 'email' => 'a@example.com', 'currency' => 'USD',
            'timezone' => 'UTC', 'password' => 'secret-password', 'password_confirmation' => 'secret-password', 'terms' => '1',
        ]);

        $this->assertDatabaseHas('tenants', ['slug' => 'peak-performance-2']);
    }

    public function test_registration_requires_terms_and_unique_email(): void
    {
        $existing = $this->gymUser();

        $this->post(route('register'), ['gym_name' => 'X', 'name' => 'Y', 'email' => $existing->email, 'currency' => 'USD', 'timezone' => 'UTC', 'password' => 'secret-password', 'password_confirmation' => 'secret-password'])
            ->assertSessionHasErrors(['email', 'terms']);
    }

    public function test_unverified_users_are_sent_to_verification(): void
    {
        $owner = User::factory()->forTenant(Tenant::factory()->create())->unverified()->create();

        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_email_can_be_verified(): void
    {
        $owner = User::factory()->forTenant(Tenant::factory()->create())->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $owner->id, 'hash' => sha1($owner->email)]);

        $this->actingAs($owner)->get($url)->assertRedirect(route('home'));
        $this->assertTrue($owner->fresh()->hasVerifiedEmail());
    }

    public function test_users_can_log_in_and_it_is_audited(): void
    {
        $owner = $this->gymUser();

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'password'])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($owner);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.login', 'user_id' => $owner->id, 'tenant_id' => $owner->tenant_id]);
        $this->assertNotNull($owner->fresh()->last_login_at);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $owner = $this->gymUser();

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(1, AuditLog::withoutTenancy()->where('event', 'auth.login_failed')->count());
    }

    public function test_login_is_rate_limited(): void
    {
        $owner = $this->gymUser();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['email' => $owner->email, 'password' => 'wrong']);
        }

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_users_can_log_out(): void
    {
        $owner = $this->gymUser();

        $this->actingAs($owner)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $owner = $this->gymUser();

        $this->post(route('password.email'), ['email' => $owner->email])->assertSessionHas('status');

        Notification::assertSentTo($owner, ResetPassword::class, function (ResetPassword $notification) use ($owner): bool {
            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $owner->email,
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'new-secret-password']);
        $this->assertAuthenticatedAs($owner);
    }

    public function test_reset_link_request_does_not_reveal_unknown_emails(): void
    {
        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');
    }

    public function test_home_routes_users_by_role(): void
    {
        $gym = Tenant::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('home'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->gymUser(Role::Owner, $gym))->get(route('home'))->assertRedirect(route('dashboard'));
        $this->actingAs($this->gymUser(Role::Member, $gym))->get(route('home'))->assertRedirect(route('portal.dashboard'));
    }
}
