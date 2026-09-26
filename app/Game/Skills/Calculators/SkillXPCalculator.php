<?php

namespace App\Game\Skills\Calculators;

use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use App\Game\Skills\Services\SkillBonusService;

class SkillXPCalculator
{
    /**
     * @param SkillBonusService $skillBonusService
     */
    public function __construct(
        private readonly SkillBonusService $skillBonusService,
    ) {}

    /**
     * Return the Skill XP earned, raised by the Skill's training bonus and the monster XP it trains towards.
     *
     * @param Skill $skill
     * @param Monster|null $monster
     * @return float|int
     */
    public function fetchSkillXP(Skill $skill, ?Monster $monster = null): float|int
    {
        $xpTowards = $this->getXpTowards($skill, $monster);
        $totalBonus = $this->skillBonusService->skillTrainingBonus($skill);

        if ($skill->can_train) {
            $base = 5 + $xpTowards;
        } else {
            $base = 25;
        }

        return $base + $base * $totalBonus;
    }

    /**
     * Return the share of the monster's XP the Skill trains towards, or the full monster XP when that share rounds to nothing.
     *
     * @param Skill $skill
     * @param Monster|null $monster
     * @return float|int
     */
    private function getXpTowards(Skill $skill, ?Monster $monster = null): float|int
    {
        if (is_null($monster)) {
            return 0;
        }

        if (is_null($skill->xp_towards)) {
            return 0;
        }

        $monsterXP = $monster->xp;
        $totalTowards = round($monsterXP - ($monsterXP * $skill->xp_towards));

        if ($totalTowards === 0.0) {
            return $monsterXP;
        }

        return $totalTowards;
    }
}
