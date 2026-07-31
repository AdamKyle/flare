<?php

namespace App\Game\Events\Concerns;

use App\Flare\Models\Character;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;

trait ShouldShowEnchantingEventButton
{
    protected function shouldShowEnchantingEventButton(Character $character): bool
    {
        return (new GlobalEventGoalEligibilityService())->canEnchantForEvent($character);
    }
}
