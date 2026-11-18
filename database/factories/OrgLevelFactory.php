<?php

namespace Database\Factories;

use App\Models\OrgLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrgLevel>
 */
class OrgLevelFactory extends Factory
{
    protected $model = OrgLevel::class;

    public function definition(): array
    {
        static $depth = 0;

        return [
            'depth' => $depth++,
            'name_key' => fake()->randomElement(['national', 'regional', 'zone', 'branch', 'sub-branch']),
            'code_required' => $depth > 1,
        ];
    }
}
