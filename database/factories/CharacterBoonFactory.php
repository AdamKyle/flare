<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\CharacterBoon;

class CharacterBoonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterBoon::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id'                            => null,
            'type'                                    => null,
            'stat_bonus'                              => null,
            'affect_skill_type'                       => null,
            'affected_skill_bonus'                    => null,
            'affected_skill_training_bonus'           => null,
            'affected_skill_base_damage_mod_bonus'    => null,
            'affected_skill_base_healing_mod_bonus'   => null,
            'affected_skill_base_ac_mod_bonus'        => null,
            'affected_skill_fight_time_out_mod_bonus' => null,
            'affected_skill_move_time_out_mod_bonus'  => null,
            'started'                                 => null,
            'complete'                                => null,
        ];
    }
}
