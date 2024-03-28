<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\Announcement;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Game\Events\Values\EventType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Jobs\MonthlyPvpAutomation;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
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
     * The console command description.
     *
     * @var string
     */
    protected $description = 'restarts the global event goal if it\'s been finished.';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void {

        $globalEvent = GlobalEventGoal::first();

        $event = Event::where('type', $globalEvent->event_type)->first();

        if (is_null($event)) {
            return;
        }

        if (!is_null($event->event_goal_steps)) {

            $this->handleStepBaseGlobalEvent($event, $globalEvent);

            return;
        }

        $this->handleRegularGlobalEvent($globalEvent);

    }

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

    private function handleStepBaseGlobalEvent(Event $event, GlobalEventGoal $globalEventGoal): void {
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
