<?php

namespace Database\Factories;

use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\GameSkill;

class GameSkillFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameSkill::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'description'                        => 'Sample',
            'name'                               => 'Sample',
            'max_level'                          => 5,
            'base_damage_mod_bonus_per_level'    => 0,
            'base_healing_mod_bonus_per_level'   => 0,
            'base_ac_mod_bonus_per_level'        => 0,
            'fight_time_out_mod_bonus_per_level' => 0,
            'move_time_out_mod_bonus_per_level'  => 0,
            'game_class_id'                      => null,
            'can_train'                          => true,
            'skill_bonus_per_level'              => 0.01,
            'type'                               => SkillTypeValue::TRAINING,
            'game_class_id'                      => null,
        ];
    }
}
