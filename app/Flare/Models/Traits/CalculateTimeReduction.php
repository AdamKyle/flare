<?php

namespace App\Flare\Models\Traits;

use App\Flare\Models\Skill;

trait CalculateTimeReduction {

    /**
     * Calculates the total bonus including boons.
     *
     * @param Skill $skill
     * @param string $modifier
     * @return float|int|mixed
     */
    public function calculateTotalTimeBonus(Skill $skill, string $modifier) {
        if (!isNull($skill->baseSkill->game_class_id)) {
            return 0.0;
        }

        $gameSkill    = $skill->baseSkill;
        $currentValue = ($gameSkill->{$modifier} * $skill->level) - $gameSkill->{$modifier};

        $character = $skill->character;

        return $currentValue + $character->boons()->where('affect_skill_type', $skill->baseSkill->type)->sum('skill_bonus');
    }
}
