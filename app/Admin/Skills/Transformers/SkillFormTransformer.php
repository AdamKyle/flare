<?php

namespace App\Admin\Skills\Transformers;

use App\Flare\Models\GameSkill;

class SkillFormTransformer
{
    /**
     * Transform a Skill into its Admin save-response / form-value representation.
     *
     * @param GameSkill $gameSkill
     * @return array
     */
    public function transform(GameSkill $gameSkill): array
    {
        return [
            'id' => $gameSkill->id,
            'name' => $gameSkill->name,
            'description' => $gameSkill->description,
            'max_level' => $gameSkill->max_level,
            'type' => $gameSkill->type,
            'game_class_id' => $gameSkill->game_class_id,
            'base_damage_mod_bonus_per_level' => $gameSkill->base_damage_mod_bonus_per_level,
            'base_healing_mod_bonus_per_level' => $gameSkill->base_healing_mod_bonus_per_level,
            'base_ac_mod_bonus_per_level' => $gameSkill->base_ac_mod_bonus_per_level,
            'fight_time_out_mod_bonus_per_level' => $gameSkill->fight_time_out_mod_bonus_per_level,
            'move_time_out_mod_bonus_per_level' => $gameSkill->move_time_out_mod_bonus_per_level,
            'unit_time_reduction' => $gameSkill->unit_time_reduction,
            'building_time_reduction' => $gameSkill->building_time_reduction,
            'unit_movement_time_reduction' => $gameSkill->unit_movement_time_reduction,
            'can_train' => $gameSkill->can_train,
            'skill_bonus_per_level' => $gameSkill->skill_bonus_per_level,
            'is_locked' => $gameSkill->is_locked === 1,
            'class_bonus' => $gameSkill->class_bonus,
        ];
    }
}
