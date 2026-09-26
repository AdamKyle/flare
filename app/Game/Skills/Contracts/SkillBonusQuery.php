<?php

namespace App\Game\Skills\Contracts;

use App\Flare\Models\Skill;

interface SkillBonusQuery
{
    /**
     * Return the Skill's current total bonus including item, boon, and Class contributions.
     *
     * @param Skill $skill
     * @return float
     */
    public function skillBonus(Skill $skill): float;
}
