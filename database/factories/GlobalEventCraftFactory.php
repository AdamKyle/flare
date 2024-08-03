<?php

namespace Database\Factories;

use App\Flare\Models\GlobalEventCraft;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlobalEventCraftFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlobalEventCraft::class;

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
            'crafts' => 10,
        ];
    }
}
