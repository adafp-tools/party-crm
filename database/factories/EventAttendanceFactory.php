<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'member_id' => Member::factory(),
            'status' => fake()->randomElement(['invited', 'attended', 'absent']),
            'client_uuid' => null,
        ];
    }

    public function attended(): static
    {
        return $this->state(['status' => 'attended']);
    }

    public function invited(): static
    {
        return $this->state(['status' => 'invited']);
    }
}
