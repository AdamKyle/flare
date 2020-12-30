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
            'max_stone'          => null,
            'max_wood'           => null,
            'max_clay'           => null,
            'max_iron'           => null,
            'current_stone'      => null,
            'current_wood'       => null,
            'current_clay'       => null,
            'current_iron'       => null,
            'current_population' => null,
            'max_population'     => null,
            'x_position'         => null,
            'y_position'         => null,
            'current_morale'     => null,
            'max_morale'         => null,
            'treasury'           => null,
        ];
    }
}
