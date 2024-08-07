<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Skill;
use League\Fractal\TransformerAbstract;

class BasicSkillsTransformer extends TransformerAbstract
{
    /**
     * Fetches skill data for a response.
     */
    public function transform(Skill $skill): array
    {
        return [
            'id' => $skill->id,
            'character_id' => $skill->character_id,
            'name' => $skill->name,
            'skill_type' => $skill->baseSkill->skillType()->getNamedValue(),
            'xp' => $skill->xp,
            'xp_max' => $skill->xp_max,
            'level' => $skill->level,
            'max_level' => $skill->baseSkill->max_level,
            'can_train' => $skill->baseSkill->can_train,
            'is_training' => $skill->currently_training,
            'is_locked' => $skill->is_locked,
            'is_class_skill' => ! is_null($skill->baseSkill->game_class_id) ? true : false,
        ];
    }
}
