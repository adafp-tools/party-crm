<?php

namespace Database\Factories;

use App\Models\OrgLevel;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrgUnit>
 */
class OrgUnitFactory extends Factory
{
    protected $model = OrgUnit::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'org_level_id' => OrgLevel::factory(),
            'name' => fake()->city(),
            'code' => strtoupper(fake()->lexify('???')),
            'path' => '/',
            'is_active' => true,
        ];
    }
}
