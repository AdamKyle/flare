<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;

class GlobalEventGoalEligibilityService
{
    public function currentCraftingGoalFor(Character $character): ?GlobalEventGoal
    {
        $event = $this->eventForCharacterMap($character);

        if (is_null($event) || ! $this->isEventRunning($event) || $event->current_event_goal_step !== GlobalEventSteps::CRAFT) {
            return null;
        }

        $goal = $event->globalEventGoals()->latest('id')->first();

        if (is_null($goal) || is_null($goal->max_crafts) || $goal->total_crafts >= $goal->max_crafts) {
            return null;
        }

        return $goal;
    }

    public function currentEnchantingGoalFor(Character $character): ?GlobalEventGoal
    {
        $event = $this->eventForCharacterMap($character);

        if (is_null($event) || ! $this->isEventRunning($event) || $event->current_event_goal_step !== GlobalEventSteps::ENCHANT) {
            return null;
        }

        $goal = $event->globalEventGoals()->latest('id')->first();

        if (is_null($goal) || is_null($goal->max_enchants) || $goal->total_enchants >= $goal->max_enchants) {
            return null;
        }

        return $goal;
    }

    /**
     * Resolves the character's map's owned event: character.map.gameMap's
     * only_during_event_type -> the active runtime Event of that type.
     */
    public function eventForCharacterMap(Character $character): ?Event
    {
        $gameMap = $character->map?->gameMap;

        if (is_null($gameMap) || is_null($gameMap->only_during_event_type)) {
            return null;
        }

        return $this->activeEventForType($gameMap->only_during_event_type);
    }

    /**
     * The runtime Event of the given type that owns an exact scheduled event
     * currently starting or running. Never resolves an event whose schedule
     * has not started, is cancelling, or has already ended.
     */
    public function activeEventForType(int $eventType): ?Event
    {
        return Event::where('type', $eventType)
            ->whereHas('scheduledEvent', function ($query) {
                $query->whereIn('status', [ScheduledEventStatus::STARTING, ScheduledEventStatus::RUNNING]);
            })
            ->orderByDesc('id')
            ->first();
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
