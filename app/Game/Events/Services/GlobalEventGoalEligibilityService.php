<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Values\GlobalEventSteps;

class GlobalEventGoalEligibilityService
{
    public function currentCraftingGoalFor(Character $character): ?GlobalEventGoal
    {
        $event = Event::where('current_event_goal_step', GlobalEventSteps::CRAFT)->first();

        if (is_null($event) || ! $this->isEventRunning($event) || ! $this->isOnEventMap($character, $event)) {
            return null;
        }

        $goal = GlobalEventGoal::where('event_type', $event->type)->first();

        if (is_null($goal) || is_null($goal->max_crafts) || $goal->total_crafts >= $goal->max_crafts) {
            return null;
        }

        return $goal;
    }

    public function currentEnchantingGoalFor(Character $character): ?GlobalEventGoal
    {
        $event = Event::where('current_event_goal_step', GlobalEventSteps::ENCHANT)->first();

        if (is_null($event) || ! $this->isEventRunning($event) || ! $this->isOnEventMap($character, $event)) {
            return null;
        }

        $goal = GlobalEventGoal::where('event_type', $event->type)->first();

        if (is_null($goal) || is_null($goal->max_enchants) || $goal->total_enchants >= $goal->max_enchants) {
            return null;
        }

        return $goal;
    }

    public function isOnEventMap(Character $character, Event $event): bool
    {
        $gameMap = GameMap::where('only_during_event_type', $event->type)->first();

        if (is_null($gameMap) || is_null($character->map)) {
            return false;
        }

        return (int) $character->map->game_map_id === (int) $gameMap->id;
    }

    public function isEventRunning(Event $event): bool
    {
        if (is_null($event->current_event_goal_step)) {
            return false;
        }

        if (! is_null($event->ends_at) && $event->ends_at <= now()) {
            return false;
        }

        return true;
    }

    public function canCraftForEvent(Character $character): bool
    {
        return ! is_null($this->currentCraftingGoalFor($character));
    }

    public function canEnchantForEvent(Character $character): bool
    {
        return ! is_null($this->currentEnchantingGoalFor($character));
    }
}
