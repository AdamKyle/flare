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

class BattleGlobalEventParticipationHandler extends BaseGlobalEventGoalParticipationHandler {

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param EventGoalsService $eventGoalService
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator, EventGoalsService $eventGoalService) {

        parent::__construct($randomAffixGenerator, $eventGoalService);
    }

    /**
     * Handle updating the global; event participation
     *
     * @param Character $character
     * @param GlobalEventGoal $globalEventGoal
     * @return void
     * @throws Exception
     */
    public function handleGlobalEventParticipation(Character $character, GlobalEventGoal $globalEventGoal) {
        if ($globalEventGoal->total_kills >= $globalEventGoal->max_kills) {
            return;
        }

        $this->handleUpdatingParticipation($character, $globalEventGoal, 'kills');

        $character = $character->refresh();

        $globalEventGoal = $globalEventGoal->refresh();

        if ($globalEventGoal->total_kills >= $globalEventGoal->next_reward_at) {
            $newAmount = $globalEventGoal->next_reward_at + $globalEventGoal->reward_every;

            $this->rewardCharactersParticipating($globalEventGoal->refresh());

            $globalEventGoal->update([
                'next_reward_at' => $newAmount >= $globalEventGoal->max_kills ? $globalEventGoal->max_kills : $newAmount,
            ]);
        }


        event(new UpdateEventGoalProgress($this->eventGoalsService->getEventGoalData($character)));

        $amount = $character->globalEventKills->kills;

        event(new UpdateEventGoalCurrentProgressForCharacter($character->user->id, $amount));
    }
}
