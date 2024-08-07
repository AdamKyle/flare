<?php

namespace Database\Factories;

use App\Flare\Models\GameBuildingUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameBuildingUnitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameBuildingUnit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'game_building_id' => 0,
            'game_unit_id' => 0,
            'required_level' => 0,
        ];
    }
}
