<?php

namespace App\Game\Skills\Services\Traits;

use App\Flare\Models\Skill;

trait SkillCheck {

    /**
     * Fetches the DC check.
     *
     * @param Skill $skill
     * @param int $dcIncrease | 0
     */
    public function getDCCheck(Skill $skill, int $dcIncrease = 0): int {

        $dcCheck = (rand(1, 1000) + ($dcIncrease !== 0 ? $dcIncrease : 0)) - $skill->level;

        if ($dcCheck > 1000) {
            return 999;
        } else if ($dcCheck <= 0) {
            return 1;
        }

        return $dcCheck;
    }

    /**
     * Fetches the characters roll.
     *
     * @param Skill $skill
     * @return mixed
     */
    public function characterRoll(Skill $skill) {
        if ( $skill->skill_bonus >= 1.0) {
            return 101; // instant success.
        }

        $roll = rand(1, 1000);
        $roll += $roll * $skill->skill_bonus;

        return $roll;
    }
}
