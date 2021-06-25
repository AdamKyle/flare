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
    public function getDCCheck(Skill $skill, int $dcIncrease = 0, int $maxRoll = 400): int {

        $dcCheck = (rand(1, $maxRoll) + ($dcIncrease !== 0 ? $dcIncrease : 0)) - $skill->level;

        if ($dcCheck > $maxRoll) {
            return $maxRoll;
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
    public function characterRoll(Skill $skill, int $maxRoll = 400) {
        return (rand(1, $maxRoll) * (1 + $skill->skill_bonus));
    }
}
