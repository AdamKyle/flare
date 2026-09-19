<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Contracts\WinterBattleRewardEligibility;
use App\Game\Events\Values\EventType;

class WinterBattleRewardEligibilityService implements WinterBattleRewardEligibility
{
    /**
     * Determine whether the character is currently eligible for a Winter battle reward.
     *
     * Eligibility requires the character to exist, an active Winter scheduled
     * event, and the character to currently be located on The Ice Plane.
     *
     * @param int $characterId
     * @return bool
     */
    public function isEligible(int $characterId): bool
    {
        $character = Character::find($characterId);

        if (is_null($character)) {
            return false;
        }

        $scheduledEvent = ScheduledEvent::where('event_type', EventType::WINTER_EVENT)
            ->where('currently_running', true)
            ->first();

        if (is_null($scheduledEvent)) {
            return false;
        }

        return $character->map?->gameMap?->mapType()->isTheIcePlane() ?? false;
    }
}
