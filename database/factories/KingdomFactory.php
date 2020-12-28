<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\Kingdom;

class KingdomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Kingdom::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id'       => null,
            'game_map_id'        => null,
            'name'               => null,
            'color'              => null,
            'stone'              => null,
            'wood'               => null,
            'clay'               => null,
            'iron'               => null,
            'current_population' => null,
            'max_population'     => null,
            'x_position'         => null,
            'y_position'         => null,
            'morale'             => null,
            'treasury'           => null,
        ];
    }
}
