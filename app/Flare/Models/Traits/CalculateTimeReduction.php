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
        $gameSkill    = $skill->baseSkill;

        if (is_null($gameSkill->{$modifier})) {
            return 0.0;
        }

        $currentValue = ($gameSkill->{$modifier} * $skill->level);

        $character = $skill->character;

        $column = null;

        if ($modifier === 'fight_time_out_mod_bonus_per_level') {
            $column = 'fight_time_out_mod_bonus';
        }

        if ($modifier === 'move_time_out_mod_bonus_per_level') {
            $column = 'move_time_out_mod_bonus';
        }

        return $currentValue + $character->boons()->where('affect_skill_type', $skill->baseSkill->type)->sum($column);
    }
}
