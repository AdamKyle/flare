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
            'name'            => $skill->name,
            'description'     => $skill->description,
            'skill_bonus'     => $skill->skill_bonus,
            'skill_xp_bonus'  => $skill->skill_training_bonus,
            'skill_type'      => $skill->baseSkill->skillType()->getNamedValue(),
            'xp'              => $skill->xp,
            'xp_max'          => $skill->xp_max,
            'level'           => $skill->level,
            'max_level'       => $skill->baseSkill->max_level,
            'can_train'       => $skill->baseSkill->can_train,
            'is_training'     => $skill->currently_training,
            'xp_towards'      => $skill->xp_towards,
            'is_locked'       => $skill->is_locked,
        ];
    }
}
