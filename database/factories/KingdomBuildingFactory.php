<?php

namespace Database\Factories;

use App\Flare\Models\KingdomBuilding;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'game_building_id' => null,
            'kingdom_id' => null,
            'level' => 1,
            'current_defence' => 300,
            'current_durability' => 300,
            'max_defence' => 300,
            'max_durability' => 300,
            'is_locked' => false,
        ];
    }
}
