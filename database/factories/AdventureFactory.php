<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\Adventure;

class AdventureFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Adventure::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'             => null,
            'description'      => null,
            'reward_item_id'   => null,
            'levels'           => null,
            'time_per_level'   => null,
            'gold_rush_chance' => null,
            'item_find_chance' => null,
            'skill_exp_bonus'  => null,
        ];
    }
}
