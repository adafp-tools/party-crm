<?php

namespace Database\Factories;

use App\Models\Interaction;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interaction>
 */
class InteractionFactory extends Factory
{
    protected $model = Interaction::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'type' => fake()->randomElement(['call', 'meeting', 'visit', 'sms', 'email', 'other']),
            'summary' => fake()->optional()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'by_user_id' => null,
        ];
    }
}
