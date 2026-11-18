<?php

namespace Database\Factories;

use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $starts = fake()->dateTimeBetween('-1 month', '+3 months');

        return [
            'org_unit_id' => OrgUnit::factory(),
            'title' => fake()->sentence(4),
            'type' => fake()->randomElement(['meeting', 'rally', 'training', 'congress', 'other']),
            'starts_at' => $starts,
            'ends_at' => (clone $starts)->modify('+2 hours'),
            'location_text' => fake()->address(),
            'created_by' => User::factory(),
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'starts_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
