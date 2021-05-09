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

        $dcCheck = (rand(1, 400) + ($dcIncrease !== 0 ? $dcIncrease : 0)) - $skill->level;

        if ($dcCheck > 400) {
            return 399;
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
        $itemBonus = 0.0;

        foreach ($skill->character->inventory->slots as $slot) {
            if ($slot->equipped) {
                $itemBonus += $slot->item->getSkillBonus($skill->name);
            }
        }

        return (rand(1, 400) * (1 + $skill->skill_bonus) + $itemBonus);
    }
}
