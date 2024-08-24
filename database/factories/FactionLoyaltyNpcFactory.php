<?php

namespace Database\Factories;

use App\Flare\Models\FactionLoyaltyNpc;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactionLoyaltyNpcFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FactionLoyaltyNpc::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'faction_loyalty_id' => null,
            'npc_id' => null,
            'current_level' => 1,
            'max_level' => 25,
            'next_level_fame' => 1000,
            'kingdom_item_defence_bonus' => 0.025,
        ];
    }
}
