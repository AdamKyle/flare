<?php

namespace App\Admin\PassiveSkills\Transformers;

use App\Flare\Models\PassiveSkill;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;

class PassiveSkillFormOptionsTransformer
{
    /**
     * Transform the internal Passive Skill form option data into its Admin API representation.
     *
     * @param array $formOptions
     * @return array
     */
    public function transform(array $formOptions): array
    {
        return [
            'effects' => collect(PassiveSkillTypeValue::getNamedValues())
                ->map(fn (string $name, int $value): array => [
                    'value' => $value,
                    'name' => $name,
                ])
                ->values()
                ->all(),
            'passive_skills' => $formOptions['passive_skills']->map(fn (PassiveSkill $passiveSkill): array => [
                'id' => $passiveSkill->id,
                'name' => $passiveSkill->name,
            ])->values()->all(),
        ];
    }
}
