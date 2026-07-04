<?php

namespace App\Game\Events\Concerns;

use App\Flare\Models\Character;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;

trait ShouldShowCraftingEventButton
{
    protected function shouldShowCraftingEventButton(Character $character): bool
    {
        return (new GlobalEventGoalEligibilityService())->canCraftForEvent($character);
    }
}
