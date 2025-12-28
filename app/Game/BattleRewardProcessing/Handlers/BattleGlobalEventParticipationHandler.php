<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Events\UpdateEventGoalCurrentProgressForCharacter;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Handlers\BaseGlobalEventGoalParticipationHandler;
use App\Game\Events\Services\EventGoalsService;
use Exception;

class BattleGlobalEventParticipationHandler extends BaseGlobalEventGoalParticipationHandler
{
    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param EventGoalsService $eventGoalService
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator, EventGoalsService $eventGoalService)
    {
        parent::__construct($randomAffixGenerator, $eventGoalService);
    }

    /**
     * Handle updating the global; event participation
     *
     * @param Character $character
     * @param GlobalEventGoal $globalEventGoal
     * @param int $killCount
     * @return void
     *
     * @throws Exception
     */
    public function handleGlobalEventParticipation(Character $character, GlobalEventGoal $globalEventGoal, int $killCount = 1): void
    {

        if ($globalEventGoal->total_kills >= $globalEventGoal->max_kills) {
            return;
        }

        $remainingKills = (int) $globalEventGoal->max_kills - (int) $globalEventGoal->total_kills;

        if ($remainingKills <= 0) {
            return;
        }

        $appliedKillCount = $killCount > $remainingKills ? $remainingKills : $killCount;

        $this->handleUpdatingParticipation($character, $globalEventGoal, 'kills', $appliedKillCount);

        $character = $character->refresh();

        $globalEventGoal = $globalEventGoal->refresh();

        $maxKills = (int) $globalEventGoal->max_kills;
        $rewardEvery = (int) $globalEventGoal->reward_every;

        if ($rewardEvery > 0) {
            while ($globalEventGoal->total_kills >= $globalEventGoal->next_reward_at) {
                $currentNextRewardAt = (int) $globalEventGoal->next_reward_at;

                $this->rewardCharactersParticipating($globalEventGoal->refresh());

                if ($currentNextRewardAt >= $maxKills) {
                    break;
                }

                $newAmount = $currentNextRewardAt + $rewardEvery;

                $globalEventGoal->update([
                    'next_reward_at' => $newAmount >= $maxKills ? $maxKills : $newAmount,
                ]);

                $globalEventGoal = $globalEventGoal->refresh();

                if ((int) $globalEventGoal->next_reward_at >= $maxKills) {
                    break;
                }
            }
        }

        event(new UpdateEventGoalProgress($this->eventGoalsService->getEventGoalData($character)));

        $currentKills = $character->globalEventKills->kills;

        event(new UpdateEventGoalCurrentProgressForCharacter($character->user->id, $currentKills));
    }
}
