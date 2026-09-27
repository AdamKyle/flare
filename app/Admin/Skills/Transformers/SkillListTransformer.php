<?php

namespace App\Admin\Skills\Transformers;

use App\Flare\Models\GameSkill;
use League\Fractal\TransformerAbstract;

class SkillListTransformer extends TransformerAbstract
{
    /**
     * Transform a Skill into its Admin list-row representation.
     *
     * @param GameSkill $gameSkill
     * @return array
     */
    public function transform(GameSkill $gameSkill): array
    {
        return [
            'id' => $gameSkill->id,
            'name' => $gameSkill->name,
            'type' => $gameSkill->type,
            'max_level' => $gameSkill->max_level,
            'can_train' => $gameSkill->can_train,
            'is_locked' => $gameSkill->is_locked === 1,
            'game_class' => is_null($gameSkill->gameClass) ? null : [
                'id' => $gameSkill->gameClass->id,
                'name' => $gameSkill->gameClass->name,
            ],
        ];
    }
}
