<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Skill;

class CraftingSkillService {

    private $character;

    public function setCharacter(Character $character) : CraftingSkillService {
        $this->character = $character;

        return $this;
    }

    public function getCurrentSkill(string $type) {
        return $this->character->skills->filter(function($skill) use($type) {
            return $skill->name === $type . ' Crafting';
        })->first();
    }

    public function fetchDCCheck(Skill $skill) {
        $dcCheck = rand(0, $skill->max_level);
        
        return $dcCheck !== 0 ? $dcCheck - $skill->level : $dcCheck / 2;
    }

    public function fetchCharacterRoll(Skill $skill) {
        return rand(0, $skill->max_level) * (1 + ($skill->skill_bonus));
    }
}