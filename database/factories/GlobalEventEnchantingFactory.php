<?php

namespace Database\Factories;

use App\Flare\Models\GlobalEventEnchant;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlobalEventEnchantingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlobalEventEnchant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'global_event_goal_id' => null,
            'character_id' => null,
            'enchants' => 10,
        ];
    }
}
