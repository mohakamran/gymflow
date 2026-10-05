<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement([Gender::Female, Gender::Male]);

        return [
            'first_name' => fake()->firstName($gender === Gender::Female ? 'female' : 'male'),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+1 555 ### ####'),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-16 years')->format('Y-m-d'),
            'gender' => $gender,
            'address' => fake()->streetAddress(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('+1 555 ### ####'),
            'joined_on' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'status' => MemberStatus::Active,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Member $member): void {
            $member->member_code ??= 'M'.Str::upper(Str::random(6));
        });
    }
}
