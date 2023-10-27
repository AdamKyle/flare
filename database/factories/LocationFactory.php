<?php

namespace Database\Factories;

use App\Flare\Models\GameMap;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\Location;

class LocationFactory extends Factory {
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
    public function definition() {
        return [
            'name'                 => 'sample',
            'game_map_id'          => GameMap::first()->id,
            'can_players_enter'    => true,
            'can_auto_battle'      => true,
            'quest_reward_item_id' => null,
            'description'          => 'sample',
            'is_port'              => false,
            'x'                    => 12,
            'y'                    => 12,
        ];
    }
}
