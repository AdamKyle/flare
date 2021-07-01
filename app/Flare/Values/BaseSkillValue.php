<?php

namespace App\Flare\Values;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;

class BaseSkillValue {

    /**
     * Get the base character skill value for a character.
     *
     * @param Charcater $character
     * @param GameSkill $skill
     * @return array
     */
    public function getBaseCharacterSkillValue(Character $character, GameSkill $skill): array {

       return [
            'character_id'       => $character->id,
            'game_skill_id'      => $skill->id,
            'currently_training' => false,
            'level'              => 1,
            'xp'                 => 0,
            'xp_max'             => $skill->can_train ? rand(150, 350) : rand(100, 250),
            'is_locked'          => $skill->is_locked,
        ];
    }

    /**
     * Get the base character skill value for a monster.
     *
     * @param Monster $monster
     * @param GameSkill $skill
     * @return array
     */
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
