<?php

namespace Database\Factories;

use App\Models\DuesPlan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DuesPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'dues_plan_id' => null,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'USD',
            'method' => fake()->randomElement(['cash', 'telebirr', 'wave', 'orange_money', 'bank']),
            'reference' => fake()->numerify('TXN-########'),
            'receipt_no' => null,
            'period_from' => now()->startOfMonth()->toDateString(),
            'period_to' => now()->endOfMonth()->toDateString(),
            'status' => 'recorded',
            'recorded_by' => User::factory(),
        ];
    }

    public function recorded(): static
    {
        return $this->state(['status' => 'recorded']);
    }

    public function confirmed(): static
    {
        return $this->state([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function reconciled(): static
    {
        return $this->state(['status' => 'reconciled']);
    }

    public function cash(): static
    {
        return $this->state([
            'method' => 'cash',
            'reference' => null,
            'receipt_no' => fake()->numerify('RCT-######'),
        ]);
    }

    public function forPlan(DuesPlan $plan): static
    {
        return $this->state([
            'dues_plan_id' => $plan->id,
            'amount' => $plan->amount,
            'currency' => $plan->currency,
        ]);
    }
}
