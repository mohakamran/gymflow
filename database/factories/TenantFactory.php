<?php

namespace Database\Factories;

use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Iron', 'Peak', 'Pulse', 'Titan', 'Forge', 'Apex', 'Core', 'Summit', 'Vital', 'Atlas']).' '.fake()->randomElement(['Fitness', 'Gym', 'Athletics', 'Strength Club', 'Training Co.']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => 'US',
            'primary_color' => fake()->randomElement(config('gym.brand_presets')),
            'currency' => 'USD',
            'timezone' => 'UTC',
            'business_hours' => Tenant::defaultBusinessHours(),
            'settings' => [
                'tax' => ['enabled' => false, 'rate' => 0, 'label' => 'Tax'],
                'invoice' => ['prefix' => 'INV-', 'footer' => 'Thank you for training with us!'],
            ],
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Tenant $tenant): void {
            $tenant->status ??= TenantStatus::Active;
            $tenant->subscription_plan ??= SubscriptionPlan::Professional;
        });
    }

    public function suspended(): static
    {
        return $this->afterMaking(fn (Tenant $tenant) => $tenant->status = TenantStatus::Suspended);
    }

    public function trial(): static
    {
        return $this->afterMaking(function (Tenant $tenant): void {
            $tenant->status = TenantStatus::Trial;
            $tenant->trial_ends_at = now()->addDays(14);
        });
    }
}
