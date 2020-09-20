<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\Skill;

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
            'character_id'          => null,
            'monster_id'            => null,
            'description'           => null,
            'name'                  => null,
            'currently_training'    => false,
            'level'                 => 1,
            'max_level'             => 100,
            'xp'                    => 0,
            'xp_max'                => rand(100, 1000),
            'base_damage_mod'       => 0.1,
            'base_healing_mod'      => 0.1,
            'base_ac_mod'           => 0.1,
            'fight_time_out_mod'    => 0.1,
            'move_time_out_mod'     => 0.1,
            'skill_bonus'           => 0.1,
            'skill_bonus_per_level' => 0.1,
            'xp_towards'            => 0.0,
        ];
    }
}
