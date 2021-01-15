<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\GameBuildingUnit;

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
            'game_building_id',
            'game_unit_id',
            'required_level',
        ];
    }
}
