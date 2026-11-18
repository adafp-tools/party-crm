<?php

namespace Database\Factories;

use App\Models\MemberSequence;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberSequence>
 */
class MemberSequenceFactory extends Factory
{
    protected $model = MemberSequence::class;

    public function definition(): array
    {
        return [
            'org_unit_id' => OrgUnit::factory(),
            'next_seq' => 1,
        ];
    }
}
