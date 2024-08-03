<?php

namespace Database\Factories;

use App\Flare\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Skill::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'currently_training' => false,
            'level' => 1,
            'xp' => 0,
            'xp_max' => rand(100, 1000),
            'xp_towards' => 0.0,
        ];
    }
}
