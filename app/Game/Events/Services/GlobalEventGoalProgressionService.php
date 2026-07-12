<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Events\GlobalMessageEvent;

class GlobalEventGoalProgressionService
{
    public function __construct(
        private readonly EventGoalsService $eventGoalsService,
    ) {}

    public function advanceIfCurrentGoalComplete(GlobalEventGoal $goal): bool
    {
        $goal = $goal->refresh();

        if (! $this->isGoalComplete($goal)) {
            return false;
        }

        $event = $goal->event;

        if (is_null($event)) {
            return false;
        }

        $characterIds = GlobalEventParticipation::where('global_event_goal_id', $goal->id)
            ->pluck('character_id')
            ->all();

        if (! is_null($event->event_goal_steps)) {
            $newGoal = $this->advanceEventToNextStep($event, $goal);

            if (is_null($newGoal)) {
                return false;
            }

            $this->broadcastGoalAdvanced($event->refresh(), $newGoal, $characterIds);

            return true;
        }

        $this->resetParticipationForCompletedGoal($goal);

        $goal->update([
            'next_reward_at' => $goal->reward_every,
        ]);

        event(new GlobalMessageEvent(
            'Global Event Goal for: '.$goal->eventType()->getNameForEvent().' Players can now participate again and earn
            Rewards for meeting the various phases! How exciting!'
        ));

        $this->broadcastGoalAdvanced($event, $goal->refresh(), $characterIds);

        return true;
    }

    public function advanceEventToNextStep(Event $event, GlobalEventGoal $completedGoal): ?GlobalEventGoal
    {
        $steps = $event->event_goal_steps;
        $currentStep = $event->current_event_goal_step;

        $index = array_search($currentStep, $steps, true);

        if ($index === false) {
            return null;
        }

        $newStep = $steps[$index + 1] ?? $steps[0];

        $event->update([
            'current_event_goal_step' => $newStep,
        ]);

        $globalEventGoalData = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents($event->type);
        $globalEventGoalData = $this->setUpDelusionalMemoriesAdditionalEventGoals($newStep, $globalEventGoalData);
        $globalEventGoalData['event_id'] = $event->id;

        $newGoal = GlobalEventGoal::create($globalEventGoalData);

        if ($newStep === GlobalEventSteps::ENCHANT) {
            GlobalEventCraftingInventory::where('global_event_goal_id', $completedGoal->id)
                ->update(['global_event_goal_id' => $newGoal->id]);
        }

        $this->resetParticipationForCompletedGoal($completedGoal);
        $completedGoal->delete();

        $gameMap = GameMap::where('only_during_event_type', $event->type)->first();

        event(new GlobalMessageEvent('Global Event Goal for: '.$newGoal->eventType()->getNameForEvent().
            ' Players can now participate in the new step: '.strtoupper($newStep).'! How exciting!'));

        if (! is_null($gameMap)) {
            event(new GlobalMessageEvent('Players can participate by going to the map: '.$gameMap->name.
                ' via Traverse (under the map for desktop, under the map inside Map Movement action drop down for mobile) '.
                'And completing either Fighting monsters, Crafting: Weapons, Spells, Armour and Rings or enchanting the already crafted items.'.
                ' You can see the event goal for the map specified by being on the map and clicking the Event Goal tab from the map.'));
        }

        return $newGoal;
    }

    public function resetParticipationForCompletedGoal(GlobalEventGoal $completedGoal): void
    {
        if ((int) $completedGoal->event_type === EventType::DELUSIONAL_MEMORIES_EVENT && ! is_null($completedGoal->max_enchants)) {
            $inventoryIds = GlobalEventCraftingInventory::where('global_event_goal_id', $completedGoal->id)->pluck('id');

            GlobalEventCraftingInventorySlot::whereIn('global_event_crafting_inventory_id', $inventoryIds)->delete();
            GlobalEventCraftingInventory::where('global_event_goal_id', $completedGoal->id)->delete();
        }

        GlobalEventParticipation::where('global_event_goal_id', $completedGoal->id)->delete();
        GlobalEventKill::where('global_event_goal_id', $completedGoal->id)->delete();
        GlobalEventCraft::where('global_event_goal_id', $completedGoal->id)->delete();
        GlobalEventEnchant::where('global_event_goal_id', $completedGoal->id)->delete();
    }

    public function broadcastGoalAdvanced(Event $event, GlobalEventGoal $newGoal, array $characterIds): void
    {
        if (GameMap::where('only_during_event_type', $event->type)->doesntExist()) {
            return;
        }

        Character::whereIn('id', $characterIds)->chunkById(250, function ($characters) {
            foreach ($characters as $character) {
                event(new UpdateEventGoalProgress($this->eventGoalsService->getEventGoalData($character)));
                event(new UpdateCharacterStatus($character));
            }
        });
    }

    private function isGoalComplete(GlobalEventGoal $goal): bool
    {
        if (! is_null($goal->max_kills)) {
            return $goal->total_kills >= $goal->max_kills;
        }

        if (! is_null($goal->max_crafts)) {
            return $goal->total_crafts >= $goal->max_crafts;
        }

        if (! is_null($goal->max_enchants)) {
            return $goal->total_enchants >= $goal->max_enchants;
        }

        return false;
    }

    private function setUpDelusionalMemoriesAdditionalEventGoals(string $newStep, array $globalEventGoalData): array
    {
        if ($newStep === GlobalEventSteps::CRAFT) {
            return GlobalEventForEventTypeValue::returnDelusionalMemoriesCraftingEventGoal();
        }

        if ($newStep === GlobalEventSteps::ENCHANT) {
            return GlobalEventForEventTypeValue::returnDelusionalMemoriesEnchantingEventGoal();
        }

        return $globalEventGoalData;
    }
}
