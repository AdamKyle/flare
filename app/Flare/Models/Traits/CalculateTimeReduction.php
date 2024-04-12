<?php

namespace App\Flare\Models\Traits;

use App\Flare\Models\Skill;
use App\Game\Character\Concerns\Boons;

trait CalculateTimeReduction {

    use Boons;

    /**
     * Calculates the total bonus including boons.
     *
     * @param Skill $skill
     * @param string $modifier
     * @return float
     */
    public function calculateTotalTimeBonus(Skill $skill, string $modifier): float {
        $gameSkill    = $skill->baseSkill;

        if (is_null($gameSkill->{$modifier})) {
            return 0.0;
        }

        $currentValue = ($gameSkill->{$modifier} * $skill->level);

        $character = $skill->character;

        $bonus = 0.0;

        if ($modifier === 'fight_time_out_mod_bonus_per_level') {
            $bonus = $this->fetchFightTimeOutModifier($character);
        }

        if ($modifier === 'move_time_out_mod_bonus_per_level') {
            $bonus = $this->fetchMoveTimOutModifier($character);
        }

        return $currentValue + $bonus;
    }
}
