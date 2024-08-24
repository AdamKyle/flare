<?php

namespace Database\Factories;

use App\Flare\Models\MarketHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MarketHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'item_id' => null,
            'sold_for' => null,
        ];
    }
}
