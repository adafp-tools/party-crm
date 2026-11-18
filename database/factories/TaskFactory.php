<?php

namespace Database\Factories;

use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'org_unit_id' => OrgUnit::factory(),
            'title' => fake()->sentence(5),
            'details' => fake()->optional()->paragraph(),
            'assignee_id' => User::factory(),
            'related_member_id' => null,
            'due_at' => fake()->dateTimeBetween('-7 days', '+30 days'),
            'status' => fake()->randomElement(['pending', 'in_progress']),
            'digest_opt_out' => false,
        ];
    }

    public function overdue(): static
    {
        return $this->state([
            'due_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status' => 'pending',
        ]);
    }

    public function done(): static
    {
        return $this->state(['status' => 'done']);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }
}
