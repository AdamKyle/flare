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
    /**
     * Resolve the currently eligible Craft Event goal for the character, when one exists.
     *
     * @param  Character  $character  The character being checked.
     * @return GlobalEventGoal|null The currently eligible Crafting goal, or null when none is eligible.
     */
    public function currentCraftingGoalFor(Character $character): ?GlobalEventGoal
    {
        $event = $this->eventForCharacterMap($character);

        if (is_null($event) || ! $this->isEventRunning($event) || $event->current_event_goal_step !== GlobalEventSteps::CRAFT) {
            return null;
        }

        $goal = $this->latestGoalFor($event);

        if (is_null($goal) || is_null($goal->max_crafts) || $goal->total_crafts >= $goal->max_crafts) {
            return null;
        }

        return $goal;
    }

    /**
     * Resolve the currently eligible Enchant Event goal for the character, when one exists.
     *
     * @param  Character  $character  The character being checked.
     * @return GlobalEventGoal|null The currently eligible Enchanting goal, or null when none is eligible.
     */
    public function currentEnchantingGoalFor(Character $character): ?GlobalEventGoal
    {
        $event = $this->eventForCharacterMap($character);

        if (is_null($event) || ! $this->isEventRunning($event) || $event->current_event_goal_step !== GlobalEventSteps::ENCHANT) {
            return null;
        }

        $goal = $this->latestGoalFor($event);

        if (is_null($goal) || is_null($goal->max_enchants) || $goal->total_enchants >= $goal->max_enchants) {
            return null;
        }

        return $goal;
    }

    /**
     * Resolve the character's map's owned event: character.map.gameMap's
     * only_during_event_type -> the active runtime Event of that type.
     *
     * @param  Character  $character  The character being checked.
     * @return Event|null The active runtime Event owned by the character's current map, or null when none exists.
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
     * Resolve the runtime Event of the given type that owns an exact scheduled event
     * currently starting or running. Never resolves an event whose schedule
     * has not started, is cancelling, or has already ended.
     *
     * @param  int  $eventType  The Event type being resolved.
     * @return Event|null The active runtime Event of the given type, or null when none is active.
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

    /**
     * Determine whether the character is currently located on the given Event's owned map.
     *
     * @param  Character  $character  The character being checked.
     * @param  Event  $event  The Event whose map is being checked.
     * @return bool True when the character is on the Event's owned map.
     */
    public function isOnEventMap(Character $character, Event $event): bool
    {
        $gameMap = GameMap::where('only_during_event_type', $event->type)->first();

        if (is_null($gameMap) || is_null($character->map)) {
            return false;
        }

        return $character->map->game_map_id === $gameMap->id;
    }

    /**
     * Determine whether the given Event is currently running.
     *
     * @param  Event  $event  The Event being checked.
     * @return bool True when the Event is currently running.
     */
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

    /**
     * Determine whether Craft For Event is currently available to the character.
     *
     * @param  Character  $character  The character being checked.
     * @return bool True when a real eligible Craft Event goal currently exists.
     */
    public function canCraftForEvent(Character $character): bool
    {
        return ! is_null($this->currentCraftingGoalFor($character));
    }

    /**
     * Determine whether Enchant For Event is currently available to the character.
     *
     * @param  Character  $character  The character being checked.
     * @return bool True when a real eligible Enchant Event goal currently exists.
     */
    public function canEnchantForEvent(Character $character): bool
    {
        return ! is_null($this->currentEnchantingGoalFor($character));
    }

    /**
     * Resolve the given Event's latest owned goal record, regardless of its current eligibility.
     *
     * @param  Event  $event  The Event whose latest goal is being resolved.
     * @return GlobalEventGoal|null The Event's latest goal record, or null when none exists.
     */
    public function latestGoalFor(Event $event): ?GlobalEventGoal
    {
        return $event->globalEventGoals()->latest('id')->first();
    }

    /**
     * Resolve a Global Event Goal by its persisted id, regardless of its current eligibility.
     *
     * Used to preserve factual final status for a goal that is no longer currently
     * eligible (completed, or the Event stepped away from its goal step).
     *
     * @param  int  $goalId  The persisted Global Event Goal id.
     * @return GlobalEventGoal|null The resolved goal, or null when it no longer exists.
     */
    public function findGoalById(int $goalId): ?GlobalEventGoal
    {
        return GlobalEventGoal::find($goalId);
    }
}
