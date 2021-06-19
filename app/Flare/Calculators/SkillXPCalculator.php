<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Adventure;
use App\Flare\Models\Skill;

class SkillXPCalculator {

    /**
     * Fetches the total skill exp.
     *
     * Applies equipment, quest item, adventure bonuses and percentage of xp towards, to skill exp which starts at a
     * a base of 5.
     *
     * @param Skill $skill
     * @param Adventure|null $adventure | null
     * @return float|int
     */
    public function fetchSkillXP(Skill $skill, Adventure $adventure = null) {
        $adventureBonus = $this->fetchAdventureBonus($adventure);
        $xpTowards      = !is_null($skill->xp_towards) ? $skill->xp_towards : 0.0;
        $totalBonus     = $xpTowards + $skill->skill_training_bonus + $adventureBonus;

        if ($totalBonus >= 1.0) {
            return 10;
        } else {
            return 5 * (1 + $totalBonus);
        }
    }

    /**
     * Returns the adventure skill training bonus.
     *
     * @param Adventure|null $adventure
     * @return float
     */
    protected function fetchAdventureBonus(Adventure $adventure = null): float {
        if (!is_null($adventure)) {
            return $adventure->skill_exp_bonus;
        }

        return 0.0;
    }
}
