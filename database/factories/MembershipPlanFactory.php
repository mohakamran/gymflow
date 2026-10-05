<?php

namespace Database\Factories;

use App\Enums\DurationUnit;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Monthly', 'Quarterly', 'Annual', 'Student', 'Off-Peak']).' '.fake()->randomElement(['Unlimited', 'Basic', 'Plus']),
            'duration_value' => 1,
            'duration_unit' => DurationUnit::Month,
            'price' => fake()->randomElement([39, 49, 59, 79]),
            'signup_fee' => 0,
            'discount_percent' => 0,
            'is_taxable' => true,
            'features' => ['Full gym access', 'Locker room'],
            'color' => '#4f46e5',
            'is_active' => true,
            'is_public' => true,
        ];
    }
}
