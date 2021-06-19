<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\GameBuilding;

class GameBuildingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameBuilding::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'                        => 'Test Building',
            'description'                 => 'Sample description',
            'max_level'                   => 100,
            'base_durability'             => 100,
            'base_defence'                => 100,
            'required_population'         => 10,
            'is_resource_building'        => false,
            'is_walls'                    => false,
            'is_church'                   => false,
            'is_farm'                     => false,
            'trains_units'                => false,
            'wood_cost'                   => 10,
            'clay_cost'                   => 10,
            'stone_cost'                  => 10,
            'iron_cost'                   => 10,
            'increase_population_amount'  => 5,
            'increase_morale_amount'      => 0.05,
            'decrease_morale_amount'      => 0.05,
            'increase_wood_amount'        => 100,
            'increase_clay_amount'        => 100,
            'increase_stone_amount'       => 100,
            'increase_iron_amount'        => 100,
            'increase_durability_amount'  => 100,
            'increase_defence_amount'     => 100,
            'time_to_build'               => 1,
            'time_increase_amount'        => 0.01,
            'units_per_level'             => null,
        ];
    }
}
