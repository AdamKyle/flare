<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Skill;

class SkillsTransformer extends TransformerAbstract {

    public function transform(Skill $skill) {

        return [
            'character_id'    => $skill->character_id,
            'monster_id'      => $skill->monster_id,
            'name'            => $skill->name,
            'skill_bonus'     => $skill->skill_bonus,
        ];
    }
}
