<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'starts_on' => now()->subDays(10)->toDateString(),
            'ends_on' => now()->addDays(20)->toDateString(),
            'status' => MembershipStatus::Active,
            'price' => 49,
            'discount_amount' => 0,
        ];
    }
}
