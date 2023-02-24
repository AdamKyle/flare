<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Skill;

class SkillsTransformer extends TransformerAbstract {

    /**
     * Fetches skill data for a response.
     *
     * @param Skill $skill
     * @return array
     */
    public function transform(Skill $skill): array {
        return [
            'id'                           => $skill->id,
            'character_id'                 => $skill->character_id,
            'name'                         => $skill->name,
            'description'                  => $skill->description,
            'skill_bonus'                  => $skill->skill_bonus,
            'skill_xp_bonus'               => $skill->skill_training_bonus,
            'skill_type'                   => $skill->baseSkill->skillType()->getNamedValue(),
            'xp'                           => $skill->xp,
            'xp_max'                       => $skill->xp_max,
            'level'                        => $skill->level,
            'max_level'                    => $skill->baseSkill->max_level,
            'can_train'                    => $skill->baseSkill->can_train,
            'is_training'                  => $skill->currently_training,
            'xp_towards'                   => $skill->xp_towards,
            'is_locked'                    => $skill->is_locked,
            'unit_time_reduction'          => $skill->unit_time_reduction,
            'building_time_reduction'      => $skill->building_time_reduction,
            'unit_movement_time_reduction' => $skill->unit_movement_time_reduction,
            'base_damage_mod'              => $skill->base_damage_mod,
            'base_healing_mod'             => $skill->base_healing_mod,
            'base_ac_mod'                  => $skill->base_ac_mod,
            'fight_timeout_mod'            => $skill->fight_timeout_mod,
            'move_timeout_mod'             => $skill->move_timeout_mod,
        ];
    }
}
