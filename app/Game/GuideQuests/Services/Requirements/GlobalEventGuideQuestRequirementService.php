<?php

namespace App\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GuideQuest;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;

class GlobalEventGuideQuestRequirementService
{
    /**
     * @param GlobalEventGoalEligibilityService $globalEventGoalEligibilityService
     */
    public function __construct(
        private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService,
    ) {}

    /**
     * Return completed Global Event participation requirement keys.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @return array
     */
    public function completedRequirements(Character $character, GuideQuest $guideQuest): array
    {
        $goal = $this->eventOwnedGoalForGuideQuest($guideQuest);

        if (is_null($goal)) {
            return [];
        }

        return array_values(array_filter([
            $this->killParticipation($character, $guideQuest, $goal),
            $this->craftParticipation($character, $guideQuest, $goal),
            $this->enchantParticipation($character, $guideQuest, $goal),
        ]));
    }

    /**
     * Return the completed event-kill requirement key.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @param GlobalEventGoal $goal
     * @return string|null
     */
    private function killParticipation(Character $character, GuideQuest $guideQuest, GlobalEventGoal $goal): ?string
    {
        if (is_null($guideQuest->required_event_goal_participation)) {
            return null;
        }

        $kills = $character->globalEventKills()->where('global_event_goal_id', $goal->id)->first()?->kills ?? 0;

        return $kills >= $guideQuest->required_event_goal_participation
            ? 'required_event_goal_participation'
            : null;
    }

    /**
     * Return the completed event-crafting requirement key.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @param GlobalEventGoal $goal
     * @return string|null
     */
    private function craftParticipation(Character $character, GuideQuest $guideQuest, GlobalEventGoal $goal): ?string
    {
        if (is_null($guideQuest->required_event_goal_crafting_participation)) {
            return null;
        }

        $crafts = $character->globalEventCrafts()->where('global_event_goal_id', $goal->id)->first()?->crafts ?? 0;

        return $crafts >= $guideQuest->required_event_goal_crafting_participation
            ? 'required_event_goal_crafting_participation'
            : null;
    }

    /**
     * Return the completed event-enchanting requirement key.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @param GlobalEventGoal $goal
     * @return string|null
     */
    private function enchantParticipation(Character $character, GuideQuest $guideQuest, GlobalEventGoal $goal): ?string
    {
        if (is_null($guideQuest->required_event_goal_enchanting_participation)) {
            return null;
        }

        $enchants = $character->globalEventEnchants()->where('global_event_goal_id', $goal->id)->first()?->enchants ?? 0;

        return $enchants >= $guideQuest->required_event_goal_enchanting_participation
            ? 'required_event_goal_enchanting_participation'
            : null;
    }

    /**
     * Return the latest goal owned by the Guide Quest's active event.
     *
     * @param GuideQuest $guideQuest
     * @return GlobalEventGoal|null
     */
    private function eventOwnedGoalForGuideQuest(GuideQuest $guideQuest): ?GlobalEventGoal
    {
        if (is_null($guideQuest->only_during_event)) {
            return null;
        }

        $event = $this->globalEventGoalEligibilityService->activeEventForType($guideQuest->only_during_event);

        return is_null($event) ? null : $event->globalEventGoals()->latest('id')->first();
    }
}
