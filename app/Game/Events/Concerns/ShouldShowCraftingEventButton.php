<?php

namespace App\Game\Events\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Values\GlobalEventSteps;

trait ShouldShowCraftingEventButton
{
    protected function shouldShowCraftingEventButton(Character $character): bool
    {
        $craftEvent = Event::where('current_event_goal_step', GlobalEventSteps::CRAFT)->first();

        if (! is_null($craftEvent)) {

            $gameMap = GameMap::where('only_during_event_type', $craftEvent->type)->first();
            $globalEvent = GlobalEventGoal::where('event_type', $craftEvent->type)->first();

            if (! is_null($gameMap) && ! is_null($globalEvent)) {
                return $character->map->game_map_id === $gameMap->id &&
                    $globalEvent->total_crafts < $globalEvent->max_crafts;
            }
        }

        return false;
    }
}
