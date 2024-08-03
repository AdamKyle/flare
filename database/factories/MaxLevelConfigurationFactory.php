<?php

namespace Database\Factories;

use App\Flare\Models\MaxLevelConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaxLevelConfigurationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MaxLevelConfiguration::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'max_level' => 1,
            'half_way' => 1,
            'three_quarters' => 1,
            'last_leg' => 1,
        ];
    }
}
