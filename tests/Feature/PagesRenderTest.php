<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        foreach (['welcome', 'login', 'register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('password.reset', ['token' => 'abc', 'email' => 'a@example.com']))->assertOk();
    }

    public function test_workspace_pages_render_for_owner(): void
    {
        $owner = $this->gymUser(Role::Owner);

        foreach (['dashboard', 'audit-logs.index', 'profile.edit'] as $route) {
            $this->actingAs($owner)->get(route($route))->assertOk()->assertSee($owner->tenant->name);
        }
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->gymUser();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.tenants.index', ['search' => 'a', 'status' => 'active']))->assertOk();
        $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_and_password_can_be_updated(): void
    {
        $owner = $this->gymUser();

        $this->actingAs($owner)->patch(route('profile.update'), ['name' => 'New Name', 'email' => $owner->email])->assertSessionHasNoErrors();
        $this->assertSame('New Name', $owner->fresh()->name);

        $this->actingAs($owner)->put(route('profile.password'), ['current_password' => 'wrong', 'password' => 'another-password', 'password_confirmation' => 'another-password'])
            ->assertSessionHasErrorsIn('password', 'current_password');

        $this->actingAs($owner)->put(route('profile.password'), ['current_password' => 'password', 'password' => 'another-password', 'password_confirmation' => 'another-password'])
            ->assertSessionHasNoErrors();
    }

    public function test_login_page_shows_copyable_demo_accounts_only_when_enabled(): void
    {
        config(['gym.show_demo_accounts' => true]);

        $response = $this->get(route('login'))->assertOk()->assertSee('Demo accounts');
        foreach (config('gym.demo_accounts') as $account) {
            $response->assertSee($account['email'])->assertSee("copyButton('{$account['email']}'", false);
        }
        $response->assertSee("copyButton('password'", false);

        config(['gym.show_demo_accounts' => false]);
        $this->get(route('login'))->assertOk()->assertDontSee('Demo accounts')->assertDontSee('admin@gymflow.test');
    }
}
