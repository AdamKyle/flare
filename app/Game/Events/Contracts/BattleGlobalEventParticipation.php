<?php

namespace App\Game\Events\Contracts;

use App\Game\Events\Values\BattleGlobalEventParticipationResult;

interface BattleGlobalEventParticipation
{
    /**
     * Apply the character's battle kill count to their currently eligible
     * Global Event goal participation, processing threshold rewards and
     * stepped-goal advancement as required.
     *
     * @param int $characterId
     * @param int $killCount
     * @return BattleGlobalEventParticipationResult
     */
    public function participate(int $characterId, int $killCount = 1): BattleGlobalEventParticipationResult;
}
