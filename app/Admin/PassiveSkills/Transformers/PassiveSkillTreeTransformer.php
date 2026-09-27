<?php

namespace App\Admin\PassiveSkills\Transformers;

use App\Flare\Models\PassiveSkill;
use League\Fractal\TransformerAbstract;

class PassiveSkillTreeTransformer extends TransformerAbstract
{
    /**
     * Transform a Passive Skill into its Admin tree representation.
     *
     * @param PassiveSkill $passiveSkill
     * @return array
     */
    public function transform(PassiveSkill $passiveSkill): array
    {
        return [
            'id' => $passiveSkill->id,
            'name' => $passiveSkill->name,
            'effect_type' => $passiveSkill->effect_type,
            'max_level' => $passiveSkill->max_level,
            'parent_id' => $passiveSkill->parent_skill_id,
            'unlocks_at_level' => $passiveSkill->unlocks_at_level,
        ];
    }
}
