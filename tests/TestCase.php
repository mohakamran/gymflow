<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed roles and permissions whenever RefreshDatabase runs.
     */
    protected string $seeder = RolesAndPermissionsSeeder::class;

    protected bool $seed = true;

    protected function gymUser(Role $role = Role::Owner, ?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::factory()->create();

        return User::factory()->forTenant($tenant, $role)->create();
    }

    /**
     * Run a callback with a gym's tenant context active (for arranging data in tests).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function inTenant(Tenant $tenant, callable $callback): mixed
    {
        return app(TenantContext::class)->run($tenant, $callback);
    }

    protected function clearTenant(): void
    {
        app(TenantContext::class)->set(null);
    }
}
