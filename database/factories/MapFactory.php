<?php

namespace Database\Factories;

use App\Flare\Models\Map;
use Illuminate\Database\Eloquent\Factories\Factory;

class MapFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Map::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => 1,
            'character_position_x' => 32,
            'character_position_y' => 32,
        ];
    }
}
