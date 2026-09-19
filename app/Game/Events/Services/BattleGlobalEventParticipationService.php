<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Events\Contracts\BattleGlobalEventParticipation;
use App\Game\Events\Events\UpdateEventGoalCurrentProgressForCharacter;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Handlers\BaseGlobalEventGoalParticipationHandler;
use App\Game\Events\Values\BattleGlobalEventParticipationResult;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;

class BattleGlobalEventParticipationService extends BaseGlobalEventGoalParticipationHandler implements BattleGlobalEventParticipation
{
    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param EventGoalsService $eventGoalsService
     * @param GlobalEventGoalEligibilityService $globalEventGoalEligibilityService
     * @param GlobalEventGoalProgressionService $globalEventGoalProgressionService
     */
    public function __construct(
        RandomAffixGenerator $randomAffixGenerator,
        EventGoalsService $eventGoalsService,
        private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService,
        private readonly GlobalEventGoalProgressionService $globalEventGoalProgressionService,
    ) {
        parent::__construct($randomAffixGenerator, $eventGoalsService);
    }

    /**
     * Apply the character's battle kill count to their currently eligible
     * Global Event goal participation, processing threshold rewards and
     * stepped-goal advancement as required.
     *
     * @param int $characterId
     * @param int $killCount
     * @return BattleGlobalEventParticipationResult
     */
    public function participate(int $characterId, int $killCount = 1): BattleGlobalEventParticipationResult
    {
        $character = Character::find($characterId);

        if (is_null($character)) {
            return BattleGlobalEventParticipationResult::none();
        }

        $event = $this->globalEventGoalEligibilityService->eventForCharacterMap($character);

        if (is_null($event)) {
            return BattleGlobalEventParticipationResult::none();
        }

        if ($event->type === EventType::DELUSIONAL_MEMORIES_EVENT && $event->current_event_goal_step !== GlobalEventSteps::BATTLE) {
            return BattleGlobalEventParticipationResult::none();
        }

        $globalEventGoal = $event->globalEventGoals()->latest('id')->first();

        if (is_null($globalEventGoal) || is_null($globalEventGoal->max_kills)) {
            return BattleGlobalEventParticipationResult::none();
        }

        return $this->applyParticipation($character, $globalEventGoal, $killCount);
    }

    /**
     * Apply the capped kill count to the goal, process threshold rewards, broadcast
     * progress, and advance a stepped goal when it has just completed.
     *
     * @param Character $character
     * @param GlobalEventGoal $globalEventGoal
     * @param int $killCount
     * @return BattleGlobalEventParticipationResult
     */
    private function applyParticipation(Character $character, GlobalEventGoal $globalEventGoal, int $killCount): BattleGlobalEventParticipationResult
    {
        if ($globalEventGoal->total_kills >= $globalEventGoal->max_kills) {
            return BattleGlobalEventParticipationResult::none();
        }

        $remainingKills = $globalEventGoal->max_kills - $globalEventGoal->total_kills;

        if ($remainingKills <= 0) {
            return BattleGlobalEventParticipationResult::none();
        }

        $appliedKillCount = $killCount > $remainingKills ? $remainingKills : $killCount;

        $this->handleUpdatingParticipation($character, $globalEventGoal, 'kills', $appliedKillCount);

        $character = $character->refresh();
        $globalEventGoal = $globalEventGoal->refresh();

        $thresholdRewardsProcessed = $this->processThresholdRewards($globalEventGoal);

        event(new UpdateEventGoalProgress($this->eventGoalsService->getEventGoalData($character)));

        $currentKills = $character->globalEventKills()
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->first()?->kills ?? 0;

        event(new UpdateEventGoalCurrentProgressForCharacter($character->user->id, $currentKills));

        $goalAdvanced = $this->advanceGoalIfSteppedAndComplete($globalEventGoal);

        return new BattleGlobalEventParticipationResult(true, $appliedKillCount, $thresholdRewardsProcessed, $goalAdvanced);
    }

    /**
     * Reward every currently qualifying participant each time the goal
     * crosses a `next_reward_at` threshold, advancing that threshold as it goes.
     *
     * @param GlobalEventGoal $globalEventGoal
     * @return int
     */
    private function processThresholdRewards(GlobalEventGoal $globalEventGoal): int
    {
        $maxKills = $globalEventGoal->max_kills;
        $rewardEvery = $globalEventGoal->reward_every;
        $processedCount = 0;

        if ($rewardEvery <= 0) {
            return $processedCount;
        }

        while ($globalEventGoal->total_kills >= $globalEventGoal->next_reward_at) {
            $currentNextRewardAt = $globalEventGoal->next_reward_at;

            $this->rewardCharactersParticipating($globalEventGoal->refresh());
            $processedCount++;

            if ($currentNextRewardAt >= $maxKills) {
                break;
            }

            $newAmount = $currentNextRewardAt + $rewardEvery;

            $globalEventGoal->update([
                'next_reward_at' => $newAmount >= $maxKills ? $maxKills : $newAmount,
            ]);

            $globalEventGoal = $globalEventGoal->refresh();

            if ($globalEventGoal->next_reward_at >= $maxKills) {
                break;
            }
        }

        return $processedCount;
    }

    /**
     * Advance the goal's owning Event to its next stepped goal when the Event
     * uses stepped goals and this goal has just completed.
     *
     * @param GlobalEventGoal $globalEventGoal
     * @return bool
     */
    private function advanceGoalIfSteppedAndComplete(GlobalEventGoal $globalEventGoal): bool
    {
        $event = $globalEventGoal->event;

        if (is_null($event?->event_goal_steps)) {
            return false;
        }

        return $this->globalEventGoalProgressionService->advanceIfCurrentGoalComplete($globalEventGoal);
    }
}
