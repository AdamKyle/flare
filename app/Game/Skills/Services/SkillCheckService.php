<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Skill;
use App\Game\Core\Chance\RandomNumberGenerator;

class SkillCheckService
{
    /**
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param SkillBonusService $skillBonusService
     */
    public function __construct(
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly SkillBonusService $skillBonusService,
    ) {}

    /**
     * Roll the difficulty check the Skill must beat, lowered by the Skill's level.
     *
     * @param Skill $skill
     * @param int $dcIncrease
     * @return int
     */
    public function getDCCheck(Skill $skill, int $dcIncrease = 0): int
    {
        $dcCheck = $this->randomNumberGenerator->numberBetween(1, 400) + $dcIncrease - $skill->level;

        if ($dcCheck > 400) {
            return 399;
        }

        if ($dcCheck <= 0) {
            return 1;
        }

        return $dcCheck;
    }

    /**
     * Roll the Character's Skill check, boosted by the Skill's bonus and guaranteed once the bonus is complete.
     *
     * @param Skill $skill
     * @return float|int
     */
    public function characterRoll(Skill $skill): float|int
    {
        $skillBonus = $this->skillBonusService->skillBonus($skill);

        if ($skillBonus >= 1.0) {
            return 401;
        }

        $roll = $this->randomNumberGenerator->numberBetween(1, 400);

        return $roll + $roll * $skillBonus;
    }
}
