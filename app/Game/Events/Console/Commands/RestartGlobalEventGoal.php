<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Services\EventGoalRestartGuardService;
use App\Game\Events\Services\EventParticipantNotifierService;
use App\Game\Events\Services\GlobalEventStepRotatorService;
use App\Game\Events\Services\RegularEventGoalResetService;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestartGlobalEventGoal extends Command
{
    protected $signature = 'restart:global-event-goal';

    protected $description = 'restarts the global event goal if it\'s been finished.';

    /**
     * @param GlobalEventStepRotatorService   $rotator
     * @param EventParticipantNotifierService $notifier
     * @param EventGoalRestartGuardService    $guard
     * @param RegularEventGoalResetService    $regularResetter
     * @return void
     */
    public function handle(
        GlobalEventStepRotatorService   $rotator,
        EventParticipantNotifierService $notifier,
        EventGoalRestartGuardService    $guard,
        RegularEventGoalResetService    $regularResetter,
    ): void {
        $globalEvent = GlobalEventGoal::first();
        if (is_null($globalEvent)) {
            return;
        }

        // Use guard to decide if we should restart at all.
        if (! $guard->shouldRestart($globalEvent)) {
            return;
        }

        $event = Event::where('type', $globalEvent->event_type)->first();
        if (is_null($event)) {
            return;
        }

        // Step-based seasonal event path:
        if (! is_null($event->event_goal_steps)) {
            try {
                $result = $rotator->rotate($event);
            } catch (\Throwable $e) {
                Log::error('Failed to rotate global event step', [
                    'event_type'   => $event->type,
                    'current_step' => $event->current_event_goal_step,
                    'message'      => $e->getMessage(),
                ]);
                return;
            }

            if ($result === null) {
                return;
            }

            $newStep = $result['new_step'];
            $newGoal = $result['new_goal'];

            $gameMap = GameMap::where('only_during_event_type', $event->type)->first();

            event(new GlobalMessageEvent(
                'Global Event Goal for: ' . $event->type .
                ' Players can now participate in the new step: ' . strtoupper($newStep) . '! How exciting!'
            ));

            if (! is_null($gameMap)) {
                event(new GlobalMessageEvent(
                    'Players can participate by going to the map: ' . $gameMap->name .
                    ' via Traverse (under the map for desktop, under the map inside Map Movement action drop down for mobile) ' .
                    'And completing either Fighting monsters, Crafting: Weapons, Spells, Armour and Rings or enchanting the already crafted items. ' .
                    'You can see the event goal for the map specified by being on the map and clicking the Event Goal tab from the map.'
                ));
            }

            // Notify participants (no-ops if count is 0, which it will be after rotation).
            $notifier->notifyForGoal($newGoal, $newGoal->globalEventParticipation()->count());
            return;
        }

        // Regular (non-step) event path:
        $regularResetter->reset($globalEvent);

        event(new GlobalMessageEvent(
            'Global Event Goal for: ' . $globalEvent->eventType()->getNameForEvent() .
            ' Players can now participate again and earn Rewards for meeting the various phases! How exciting!'
        ));

        $notifier->notifyForGoal($globalEvent, $globalEvent->globalEventParticipation()->count());
    }
}
