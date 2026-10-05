<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed roles plus development accounts. Demo accounts are only created outside production.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        if (app()->isProduction()) {
            return;
        }

        User::factory()->superAdmin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@gymflow.test',
        ]);

        $gym = Tenant::factory()->create([
            'name' => 'Iron Peak Fitness',
            'slug' => 'iron-peak-fitness',
            'email' => 'hello@ironpeak.test',
            'primary_color' => '#4f46e5',
            'public_profile_enabled' => true,
        ]);
        $gym->forceFill(['status' => TenantStatus::Active, 'subscription_plan' => SubscriptionPlan::Professional])->save();

        $accounts = [
            ['Olivia Owner', 'owner@gymflow.test', Role::Owner],
            ['Sam Reception', 'staff@gymflow.test', Role::Staff],
            ['Tariq Trainer', 'trainer@gymflow.test', Role::Trainer],
            ['Maya Member', 'member@gymflow.test', Role::Member],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::factory()->forTenant($gym, $role)->create(['name' => $name, 'email' => $email]);
        }

        $this->callWith(DemoGymSeeder::class, ['gym' => $gym]);

        // A second, smaller gym to demonstrate data isolation.
        $otherGym = Tenant::factory()->trial()->create(['name' => 'Summit Strength Club', 'slug' => 'summit-strength-club', 'primary_color' => '#059669']);
        User::factory()->forTenant($otherGym)->create(['name' => 'Omar Owner', 'email' => 'owner2@gymflow.test']);

        app(TenantContext::class)->run($otherGym, function () use ($otherGym): void {
            MembershipPlan::factory()->create(['name' => 'Summit Monthly', 'price' => 45]);

            foreach (range(1, 6) as $i) {
                Member::factory()->create(['member_code' => 'M'.str_pad((string) $i, 5, '0', STR_PAD_LEFT), 'tenant_id' => $otherGym->id]);
            }

            $otherGym->forceFill(['member_counter' => 6])->save();
        });
    }
}
