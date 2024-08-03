<?php

namespace Database\Factories;

use App\Flare\Models\MarketBoard;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketBoardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MarketBoard::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'item_id' => null,
            'listed_price' => null,
        ];
    }
}
