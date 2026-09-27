<?php

namespace App\Admin\PassiveSkills\Transformers;

use App\Flare\Models\PassiveSkill;
use League\Fractal\TransformerAbstract;

class PassiveSkillListTransformer extends TransformerAbstract
{
    /**
     * Transform a Passive Skill into its Admin list-row representation.
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
            'is_locked' => $passiveSkill->is_locked,
            'is_parent' => $passiveSkill->is_parent,
            'parent' => is_null($passiveSkill->parent) ? null : [
                'id' => $passiveSkill->parent->id,
                'name' => $passiveSkill->parent->name,
            ],
        ];
    }
}
