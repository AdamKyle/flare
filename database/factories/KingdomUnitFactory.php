<?php

namespace Database\Factories;

use App\Flare\Models\KingdomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class KingdomUnitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = KingdomUnit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'kingdom_id' => null,
            'game_unit_id' => null,
            'amount' => 1000,
        ];
    }
}
