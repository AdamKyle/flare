<?php

namespace Database\Factories;

use App\Flare\Models\GlobalEventParticipation;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlobalEventParticipationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlobalEventParticipation::class;

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
            'current_kills' => null,
            'current_crafts' => null,
            'current_enchants' => null,
        ];
    }
}
