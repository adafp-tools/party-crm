<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberStatusHistory>
 */
class MemberStatusHistoryFactory extends Factory
{
    protected $model = MemberStatusHistory::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'from_status' => 'applicant',
            'to_status' => 'active',
            'reason' => fake()->optional()->sentence(),
            'changed_by' => null,
            'changed_at' => now(),
        ];
    }
}
