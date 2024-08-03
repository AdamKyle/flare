<?php

namespace Database\Factories;

use App\Flare\Models\GameUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'name' => 'Spearmen',
            'description' => 'Test unit',
            'attack' => 1,
            'defence' => 1,
            'can_heal' => false,
            'heal_percentage' => null,
            'siege_weapon' => false,
            'is_airship' => false,
            'attacker' => true,
            'defender' => false,
            'wood_cost' => 10,
            'clay_cost' => 10,
            'stone_cost' => 10,
            'iron_cost' => 10,
            'steel_cost' => 0,
            'required_population' => 1,
            'time_to_recruit' => 1,
        ];
    }
}
