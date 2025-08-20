<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use App\Game\Events\Values\GlobalEventSteps;
use Illuminate\Support\Facades\DB;
use Throwable;

class GlobalEventStepRotatorService
{
    /**
     * @param GlobalEventGoalCleanupService $cleanup
     */
    public function __construct(private GlobalEventGoalCleanupService $cleanup) {}

    /**
     * Advance the given seasonal/step-based event to its next step atomically.
     *
     * Returns null if the current step cannot be found in the ordered steps.
     *
     * @param Event $event
     * @return array{new_step:string,new_goal:GlobalEventGoal}|null
     * @throws Throwable
     */
    public function rotate(Event $event): ?array
    {
        $steps       = $event->event_goal_steps;
        $currentStep = $event->current_event_goal_step;

        $index = is_array($steps) ? array_search($currentStep, $steps, true) : false;
        if ($index === false) {
            return null;
        }

        $newStep = $steps[$index + 1] ?? $steps[0];

        $newGoal = DB::transaction(function () use ($event, $currentStep, $newStep) {
            if ($currentStep === GlobalEventSteps::ENCHANT) {
                $this->cleanup->purgeEnchantInventories();
            }

            $this->cleanup->purgeCoreAndGoal();

            $event->update([
                'current_event_goal_step' => $newStep,
            ]);

            $goalData = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents($event->type);

            if ($newStep === GlobalEventSteps::CRAFT) {
                $goalData = GlobalEventForEventTypeValue::returnDelusionalMemoriesCraftingEventGoal($event->type);
            } elseif ($newStep === GlobalEventSteps::ENCHANT) {
                $goalData = GlobalEventForEventTypeValue::returnDelusionalMemoriesEnchantingEventGoal($event->type);
            }

            return GlobalEventGoal::create($goalData);
        });

        return ['new_step' => $newStep, 'new_goal' => $newGoal];
    }
}
