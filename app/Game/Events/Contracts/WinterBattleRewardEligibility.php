<?php

namespace App\Game\Events\Contracts;

interface WinterBattleRewardEligibility
{
    /**
     * Determine whether the character is currently eligible for a Winter battle reward.
     *
     * @param int $characterId
     * @return bool
     */
    public function isEligible(int $characterId): bool;
}
