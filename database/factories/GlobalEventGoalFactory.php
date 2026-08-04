<?php

namespace Database\Factories;

use App\Flare\Models\GlobalEventGoal;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
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
            'event_id' => null,
            'max_kills' => null,
            'max_crafts' => null,
            'max_enchants' => null,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => null,
            'should_be_mythic' => false,
        ];
    }
}
