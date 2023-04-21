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
            'name'               => 'Sample',
            'color'              => [193, 66, 66, 1],
            'max_stone'          => 2000,
            'max_wood'           => 2000,
            'max_clay'           => 2000,
            'max_iron'           => 2000,
            'current_stone'      => 2000,
            'current_wood'       => 2000,
            'current_clay'       => 2000,
            'current_iron'       => 2000,
            'current_population' => 2000,
            'max_population'     => 2000,
            'x_position'         => 16,
            'y_position'         => 16,
            'current_morale'     => .50,
            'max_morale'         => 1.0,
            'treasury'           => null,
            'last_walked'        => now()->subWeeks(6),
        ];
    }
}
