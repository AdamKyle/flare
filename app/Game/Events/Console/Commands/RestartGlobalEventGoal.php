<?php

namespace App\Game\Events\Console\Commands;

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
use App\Flare\Models\Map;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;

class RestartGlobalEventGoal extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restart:global-event-goal';

    /**
     * The console command description.Set the battle event.
     *
     * @var string
     */
    protected $description = 'restarts the global event goal if it\'s been finished.';

    /**
     * Execute the console command.
     *
     * @param EventGoalsService $eventGoalsService
     * @return void
     */
    public function handle(EventGoalsService $eventGoalsService): void {

        $globalEvent = GlobalEventGoal::first();

        $event = Event::where('type', $globalEvent->event_type)->first();

        if (is_null($event)) {
            return;
        }

        if (!is_null($event->event_goal_steps)) {

            $this->handleStepBaseGlobalEvent($event);

            return;
        }

        $this->handleRegularGlobalEvent($globalEvent);

        $this->updateCharactersGlobalMapEvents($eventGoalsService, $globalEvent);
    }

    /**
     * Update all characters global map events on the map of the event.
     *
     * @param EventGoalsService $eventGoalsService
     * @param GlobalEventGoal $globalEventGoal
     * @return void
     */
    private function updateCharactersGlobalMapEvents(EventGoalsService $eventGoalsService, GlobalEventGoal $globalEventGoal): void {
        $gameMap = GameMap::where('only_during_event_type', $globalEventGoal->event_type)->first();

        if (is_null($gameMap)) {
            return;
        }

        $characterIds = Map::where('game_map_id', $gameMap->id)->pluck('character_id')->toArray();

        Character::whereIn('id', $characterIds)->chunkById(250, function($characters) use ($eventGoalsService) {
            foreach ($characters as $character) {
                event(
                    new UpdateEventGoalProgress(
                        $eventGoalsService->getEventGoalData($character)
                    )
                );
            }
        });
    }

    /**
     * Handle regular global events.
     *
     * @param GlobalEventGoal $globalEventGoal
     * @return void
     */
    private function handleRegularGlobalEvent(GlobalEventGoal $globalEventGoal): void {
        if ($globalEventGoal->total_kills < $globalEventGoal->max_kills) {
            return;
        }

        $globalEventGoal->update([
            'next_reward_at' => $globalEventGoal->reward_every,
        ]);

        $globalEvent = $globalEventGoal->refresh();

        $globalEvent->globalEventParticipation()->truncate();
        $globalEvent->globalEventKills()->truncate();

        event(new GlobalMessageEvent(
            'Global Event Goal for: ' . $globalEvent->eventType()->getNameForEvent(). ' Players can now participate again and earn
            Rewards for meeting the various phases! How exciting!'
        ));
    }

    /**
     * Set up the base global event
     *
     * @param Event $event
     * @return void
     */
    private function handleStepBaseGlobalEvent(Event $event): void {
        $steps       = $event->event_goal_steps;
        $currentStep = $event->current_event_goal_step;

        $index = array_search($currentStep, $steps);

        if ($index === false) {
            return;
        }

        $newIndex = $index + 1;

        if (!isset($steps[$newIndex])) {
            $newStep = $steps[0];
        } else {
            $newStep = $steps[$newIndex];
        }

        if ($currentStep === GlobalEventSteps::ENCHANT) {
            GlobalEventCraftingInventorySlot::truncate();
            GlobalEventCraftingInventory::truncate();
        }

        GlobalEventParticipation::truncate();
        GlobalEventKill::truncate();
        GlobalEventCraft::truncate();
        GlobalEventEnchant::truncate();
        GlobalEventGoal::truncate();

        $event->update([
            'current_event_goal_step' => $newStep,
        ]);

        $globalEventGoalData = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents($event->type);

        if ($newStep === GlobalEventSteps::CRAFT) {
            $globalEventGoalData = GlobalEventForEventTypeValue::returnCraftingEventGoal();
        }

        if ($newStep === GlobalEventSteps::ENCHANT) {
            $globalEventGoalData = GlobalEventForEventTypeValue::returnEnchantingEventGoal();
        }

        $globalEventGoal = GlobalEventGoal::create($globalEventGoalData);

        event(new GlobalMessageEvent('Global Event Goal for: ' . $globalEventGoal->eventType()->getNameForEvent() .
            ' Players can now participate in the new step: ' . strtoupper($newStep) . '! How exciting!'));

    }
}
