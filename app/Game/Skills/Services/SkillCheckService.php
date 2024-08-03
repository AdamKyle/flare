<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Skill;

class SkillCheckService
{
    /**
     * Fetches the DC check.
     *
     * @param  int  $dcIncrease  | 0
     */
    public function getDCCheck(Skill $skill, int $dcIncrease = 0): int
    {

        $dcCheck = (rand(1, 400) + ($dcIncrease !== 0 ? $dcIncrease : 0)) - $skill->level;

        if ($dcCheck > 400) {
            // @codeCoverageIgnoreStart
            return 399;
            // @codeCoverageIgnoreEnd
        } elseif ($dcCheck <= 0) {
            // @codeCoverageIgnoreStart
            return 1;
            // @codeCoverageIgnoreEnd
        }

        return $dcCheck;
    }

    /**
     * Fetches the characters roll.
     */
    public function characterRoll(Skill $skill): float|int
    {
        if ($skill->skill_bonus >= 1.0) {
            return 401;
        }

        $roll = rand(1, 400);
        $roll += $roll * $skill->skill_bonus;

        return $roll;
    }
}
