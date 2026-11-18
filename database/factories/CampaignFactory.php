<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'org_unit_id' => null,
            'type' => fake()->randomElement(['sms', 'email']),
            'title' => fake()->sentence(4),
            'subject' => null,
            'body' => fake()->paragraph(),
            'status' => 'draft',
            'scheduled_at' => null,
            'sent_at' => null,
            'recipient_count' => 0,
        ];
    }

    public function sms(): static
    {
        return $this->state(['type' => 'sms', 'subject' => null]);
    }

    public function email(): static
    {
        return $this->state(fn () => [
            'type' => 'email',
            'subject' => fake()->sentence(5),
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => 'sent',
            'sent_at' => now()->subHour(),
            'recipient_count' => fake()->numberBetween(10, 500),
        ]);
    }

    public function forUnit(): static
    {
        return $this->state(['org_unit_id' => OrgUnit::factory()]);
    }
}
