<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\KingdomBuilding;

class KingdomBuildingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = KingdomBuilding::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'game_building_id'   => null,
            'kingdom_id'        => null,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 300,
            'max_defence'        => 300,
            'max_durability'     => 300,
        ];
    }
}
