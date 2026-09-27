<?php

namespace App\Admin\Skills\Transformers;

use App\Flare\Models\GameSkill;

class SkillDetailTransformer
{
    /**
     * @param SkillFormTransformer $skillFormTransformer
     */
    public function __construct(private readonly SkillFormTransformer $skillFormTransformer) {}

    /**
     * Transform a Skill into its Admin detail representation, including its Class identity.
     *
     * @param GameSkill $gameSkill
     * @return array
     */
    public function transform(GameSkill $gameSkill): array
    {
        return array_merge($this->skillFormTransformer->transform($gameSkill), [
            'game_class' => is_null($gameSkill->gameClass) ? null : [
                'id' => $gameSkill->gameClass->id,
                'name' => $gameSkill->gameClass->name,
            ],
        ]);
    }
}
