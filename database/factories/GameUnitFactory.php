<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\GameUnit;

class GameUnitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameUnit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'                   => 'Spearmen',
            'description'            => 'Test unit',
            'attack'                 => 1,
            'defence'                => 1,
            'can_heal'               => false,
            'heal_percentage'        => null,
            'siege_weapon'           => false,
            'attacker'               => true,
            'defender'               => false,
            'primary_target'         => null,
            'fall_back'              => null,
            'travel_time'            => 1,
            'wood_cost'              => 10,
            'clay_cost'              => 10,
            'stone_cost'             => 10,
            'iron_cost'              => 10,
            'required_population'    => 1,
            'time_to_recruit'        => 1,
            'primary_target'         => null,
            'fall_back'              => null,
        ];
    }
}
