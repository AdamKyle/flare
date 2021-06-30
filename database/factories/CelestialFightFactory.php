<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\CelestialFight;

class CelestialFightFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CelestialFight::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'monster_id'      => null,
            'character_id'    => null,
            'conjured_at'     => null,
            'x_position'      => null,
            'y_position'      => null,
            'damaged_kingdom' => null,
            'stole_treasury'  => null,
            'weakened_morale' => null,
            'current_health'  => null,
            'max_health'      => null,
            'type'            => null,
        ];
    }
}
