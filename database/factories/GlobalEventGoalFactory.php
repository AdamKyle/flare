<?php

namespace Database\Factories;

use App\Flare\Models\GlobalEventGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlobalEventGoalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlobalEventGoal::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'max_kills' => null,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'event_type' => null,
            'item_specialty_type_reward' => null,
            'should_be_unique' => true,
            'unique_type' => null,
            'should_be_mythic' => false,
        ];
    }
}
