<?php

namespace App\Admin\PassiveSkills\Transformers;

use App\Flare\Models\PassiveSkill;
use Illuminate\Support\Collection;

class PassiveSkillDetailTransformer
{
    /**
     * @param PassiveSkillFormTransformer $passiveSkillFormTransformer
     */
    public function __construct(private readonly PassiveSkillFormTransformer $passiveSkillFormTransformer) {}

    /**
     * Transform a Passive Skill into its Admin detail representation, including its parent and direct children.
     *
     * @param PassiveSkill $passiveSkill
     * @param Collection $childSkills
     * @return array
     */
    public function transform(PassiveSkill $passiveSkill, Collection $childSkills): array
    {
        return array_merge($this->passiveSkillFormTransformer->transform($passiveSkill), [
            'parent' => is_null($passiveSkill->parent) ? null : [
                'id' => $passiveSkill->parent->id,
                'name' => $passiveSkill->parent->name,
                'effect_type' => $passiveSkill->parent->effect_type,
                'max_level' => $passiveSkill->parent->max_level,
            ],
            'child_skills' => $childSkills->map(fn (PassiveSkill $childSkill): array => [
                'id' => $childSkill->id,
                'name' => $childSkill->name,
                'effect_type' => $childSkill->effect_type,
                'max_level' => $childSkill->max_level,
                'unlocks_at_level' => $childSkill->unlocks_at_level,
            ])->values()->all(),
        ]);
    }
}
