<?php

namespace App\Flare\Services;

use App\Flare\Calculators\XPCalculator as CalculatorsXPCalculator;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use App\Flare\Events\UpdateSkillEvent;
use Facades\App\Flare\Calculators\XPCalculator;

class CharacterRewardService {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * Constructor
     * 
     * @param Character $character
     * @return void
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }

    /**
     * Distribute the gold and xp to the character.
     * 
     * @param Monster $monster
     * @param Adventure $adventure | null
     * @return void
     */
    public function distributeGoldAndXp(Monster $monster, Adventure $adventure = null) {
        $currentSkill = $this->fetchCurrentSkillInTraining();
        $xpReduction  = 0.0;
        
        if (!is_null($currentSkill)) {
            $xpReduction = $currentSkill->xp_towards;

            $this->trainSkill($currentSkill, $adventure);
        }

        $this->character->xp   += XPCalculator::fetchXPFromMonster($monster, $this->character->level, $xpReduction);
        $this->character->gold += $monster->gold;

        $this->character->save();
    }

    /**
     * Get the refreshed Character
     * 
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character->refresh();
    }

    /**
     * Get the skill in trainind or null
     * 
     * @return mixed
     */
    public function fetchCurrentSkillInTraining() {
        return $this->character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();
    }

    /**
     * Fire the update skill event.
     * 
     * @param Skill $skill
     * @param Adventure $adventure | nul
     * @return void
     */
    public function trainSkill(Skill $skill, Adventure $adventure = null) {
        event(new UpdateSkillEvent($skill, $adventure));
    }
}