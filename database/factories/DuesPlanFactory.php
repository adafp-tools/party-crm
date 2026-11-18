<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DuesPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Dues',
            'amount' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'USD',
            'period' => fake()->randomElement(['monthly', 'yearly', 'one_off']),
            'applies_to' => fake()->randomElement(['all', 'member', 'supporter', 'youth_wing', 'women_wing']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function monthly(): static
    {
        return $this->state(['period' => 'monthly']);
    }

    public function yearly(): static
    {
        return $this->state(['period' => 'yearly']);
    }
}
