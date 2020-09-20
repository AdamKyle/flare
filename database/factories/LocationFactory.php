<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\Location;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'                 => null,
            'game_map_id'          => null,
            'quest_reward_item_id' => null,
            'description'          => null,
            'is_port'              => null,
            'x'                    => null,
            'y'                    => null,
        ];
    }
}
