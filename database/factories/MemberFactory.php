<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'org_unit_id' => OrgUnit::factory(),
            'membership_no' => null,
            'first_name' => fake()->firstName(),
            'middle_name' => null,
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'category' => 'member',
            'status' => 'active',
            'joined_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'is_youth_wing' => false,
            'is_women_wing' => false,
            'volunteer' => false,
            'sms_opt_out' => false,
            'email_opt_out' => false,
        ];
    }

    public function applicant(): static
    {
        return $this->state(['status' => 'applicant', 'membership_no' => null]);
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function supporter(): static
    {
        return $this->state(['category' => 'supporter']);
    }
}
