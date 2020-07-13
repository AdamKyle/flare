<?php

namespace App\Flare\Services;

use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use App\Flare\Events\UpdateSkillEvent;

class CharacterRewardService {

    private $character;

    public function __construct(Character $character) {
        $this->character = $character;
    }

    public function distributeGoldAndXp(Monster $monster, Adventure $adventure = null) {
        $currentSkill = $this->fetchCurrentSkillInTraining();
        $xpReduction  = 0.0;

        if (!is_null($currentSkill)) {
            $xpReduction = $currentSkill->xp_towards;

            $this->trainSkill($currentSkill, $adventure);
        }

        $this->character->xp = $this->fetchMonsterXp($monster, $xpReduction);
        $this->character->gold += $monster->gold;

        $this->character->save();
    }

    public function getCharacter(): Character {
        return $this->character->refresh();
    }

    public function fetchMonsterXp(Monster $monster, float $xpReduction = 0.0) {

        if ($monster->max_level === 0) {
            // Always Just give.
            $xp = $this->character->xp + ($xpReduction !==  0.0 ? ($monster->xp - ($monster->xp * $xpReduction)) : $monster->xp);
        } else if ($this->character->level < $monster->max_level) {
            // So the monster has a max exp level and the character is below it, so they get full xp.
            $xp = $this->character->xp + ($xpReduction !==  0.0 ? ($monster->xp - ($monster->xp * $xpReduction)) : $monster->xp);
        } else if ($this->character->level > $monster->max_level) {
            // So the monster has a max exp level and the character is above it, so they get 1/3rd xp.
            $xp = $this->character->xp + ($xpReduction !==  0.0 ? (3.3333 - (3.3333 * $xpReduction)) : 3.3333);
        }

        return $xp;
    }

    public function fetchCurrentSkillInTraining() {
        return $this->character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();
    }

    public function trainSkill(Skill $skill, Adventure $adventure = null) {
        event(new UpdateSkillEvent($skill, $adventure));
    }
}