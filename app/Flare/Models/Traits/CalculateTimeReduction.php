<?php

namespace App\Flare\Models\Traits;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;

trait CalculateTimeReduction {

    public function calculateTotalBonus(GameSkill $gameSkill, Skill $skill, string $modifier) {
        $currentValue = ($gameSkill->{$modifier} * $skill) - $gameSkill->{$modifier};

        $character = $skill->character;

        return $currentValue + $character->boons()->where('affect_skill_type', $skill->baseSkill->type)->sum('skill_bonus');
    }
}
