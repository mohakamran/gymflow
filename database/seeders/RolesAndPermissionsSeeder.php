<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: safe to run on every deploy to sync roles with the permission map in App\Enums\Role.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::everything() as $permission) {
            PermissionModel::findOrCreate($permission, 'web');
        }

        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role->value, 'web')->syncPermissions($role->permissions());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
