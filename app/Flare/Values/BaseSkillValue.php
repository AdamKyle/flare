<?php

namespace App\Flare\Values;

use Illuminate\Support\Str;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;

class BaseSkillValue {

    public function getBaseCharacterSkillValue(Character $character, GameSkill $skill): array {
        
       return [
            'character_id'       => $character->id,
            'game_skill_id'      => $skill->id,
            'currently_training' => false,
            'level'              => 1,
            'xp'                 => 0,
            'xp_max'             => $skill->can_train ? rand(100, 150) : rand(100, 200),
        ];
    }

    public function getBaseMonsterSkillValue(Monster $monster, GameSkill $skill): array {

        return [
            'monster_id'         => $monster->id,
            'game_skill_id'      => $skill->id,
            'currently_training' => false,
            'level'              => 0,
            'xp'                 => 0,
        ];
    }
}
