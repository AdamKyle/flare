<?php

namespace Database\Factories;

use App\Flare\Models\AlchemyBagSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlchemyBagSlotFactory extends Factory
{
    protected $model = AlchemyBagSlot::class;

    public function definition(): array
    {
        return [
            'alchemy_bag_id' => 0,
            'character_id' => 0,
            'item_id' => 0,
            'amount' => 1,
        ];
    }
}
