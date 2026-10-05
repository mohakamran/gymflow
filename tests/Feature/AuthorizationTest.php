<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TenantStatus;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{Role, string, int}>
     */
    public static function accessMatrix(): array
    {
        return [
            'owner dashboard' => [Role::Owner, 'dashboard', 200],
            'owner settings' => [Role::Owner, 'settings.profile.edit', 200],
            'owner audit' => [Role::Owner, 'audit-logs.index', 200],
            'owner admin' => [Role::Owner, 'admin.dashboard', 403],
            'staff dashboard' => [Role::Staff, 'dashboard', 200],
            'staff settings' => [Role::Staff, 'settings.profile.edit', 403],
            'staff audit' => [Role::Staff, 'audit-logs.index', 403],
            'trainer dashboard' => [Role::Trainer, 'dashboard', 200],
            'trainer settings' => [Role::Trainer, 'settings.branding.edit', 403],
            'member dashboard' => [Role::Member, 'dashboard', 403],
            'member portal' => [Role::Member, 'portal.dashboard', 200],
            'member settings' => [Role::Member, 'settings.profile.edit', 403],
            'staff portal' => [Role::Staff, 'portal.dashboard', 403],
        ];
    }

    #[DataProvider('accessMatrix')]
    public function test_role_access(Role $role, string $route, int $status): void
    {
        $user = $this->gymUser($role);

        if ($role === Role::Member) {
            Member::factory()->create(['tenant_id' => $user->tenant_id, 'user_id' => $user->id]);
        }

        $this->actingAs($user)->get(route($route))->assertStatus($status);
    }

    public function test_staff_cannot_update_settings(): void
    {
        $staff = $this->gymUser(Role::Staff);

        $this->actingAs($staff)->put(route('settings.profile.update'), ['name' => 'Hacked'])->assertForbidden();
        $this->assertNotSame('Hacked', $staff->tenant->fresh()->name);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_super_admin_can_manage_gyms(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $gym = Tenant::factory()->create();

        $this->actingAs($admin)->get(route('admin.tenants.index'))->assertOk()->assertSee($gym->name);
        $this->actingAs($admin)->get(route('admin.tenants.show', $gym))->assertOk();

        $this->actingAs($admin)->patch(route('admin.tenants.update', $gym), ['status' => 'suspended', 'subscription_plan' => 'business'])
            ->assertRedirect(route('admin.tenants.show', $gym));

        $this->assertSame(TenantStatus::Suspended, $gym->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'tenant.updated', 'tenant_id' => $gym->id]);
    }

    public function test_super_admin_is_kept_out_of_gym_workspace(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('home'));
    }
}
