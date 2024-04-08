<?php

namespace App\Game\Events\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Values\GlobalEventSteps;

trait ShouldShowEnchantingEventButton {

    protected function shouldShowEnchantingEventButton(Character $character): bool {
        $enchantingEvent = Event::where('current_event_goal_step', GlobalEventSteps::ENCHANT)->first();

        if (!is_null($enchantingEvent)) {

            $gameMap = GameMap::where('only_during_event_type', $enchantingEvent->type)->first();
            $globalEvent = GlobalEventGoal::where('event_type', $enchantingEvent->type)->first();

            if (!is_null($gameMap) && !is_null($globalEvent)) {
                return $character->map->game_map_id === $gameMap->id &&
                    $globalEvent->total_enchants < $globalEvent->max_enchants;
            }
        }

        return false;
    }
}
