<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Skill;

class SkillsTransformer extends TransformerAbstract {

    /**
     * Fetches skill data for a response.
     * 
     * @param Skill $skill
     */
    public function transform(Skill $skill) {
        return [
            'id'              => $skill->id,
            'character_id'    => $skill->character_id,
            'monster_id'      => $skill->monster_id,
            'name'            => $skill->name,
            'skill_bonus'     => $skill->skill_bonus,
            'skill_type'      => $skill->baseSkill->skillType()->getNamedValue(),
            'xp'              => $skill->xp,
            'xp_max'          => $skill->xp_max,
            'current_xp'      => !is_null($skill->xp_max) ? ($skill->xp / $skill->xp_max) * 100 : 0,
            'level'           => $skill->level,
            'max_level'       => $skill->baseSkill->max_level,
            'can_train'       => $skill->baseSkill->can_train,
            'is_training'     => $skill->currently_training,
            'xp_towards'      => $skill->xp_towards,
            'is_locked'       => $skill->is_locked,
        ];
    }
}
