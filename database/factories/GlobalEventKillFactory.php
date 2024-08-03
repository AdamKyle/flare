<?php

namespace Database\Factories;

use App\Flare\Models\GlobalEventKill;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlobalEventKillFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlobalEventKill::class;

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
            'kills' => 10,
        ];
    }
}
