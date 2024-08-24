<?php

namespace Database\Factories;

use App\Flare\Models\WeeklyMonsterFight;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyMonsterFightFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WeeklyMonsterFight::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'monster_id' => null,
            'character_deaths' => 0,
            'monster_was_killed' => false,
        ];
    }
}
