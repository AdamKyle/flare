<?php

namespace App\Game\Events\Services;


use App\Flare\Models\Character;
use App\Game\Core\Traits\ResponseBuilder;
use App\Flare\Models\GlobalEventGoal;


class EventGoalsService {

    use ResponseBuilder;

    /**
     * Fetches the current event goal data for controller action.
     *
     * @param Character $character
     * @return array
     */
    public function fetchCurrentEventGoal(Character $character): array {
        return $this->successResult($this->getEventGoalData($character));
    }

    /**
     * Get the event goal data.
     *
     * @param Character $character
     * @return array
     */
    public function getEventGoalData(Character $character): array {
        $globalEventGoal = GlobalEventGoal::first();
        $characterKills  = 0;
        $characterCrafts = 0;

        if (!is_null($character->globalEventKills)) {
            $characterKills = $character->globalEventKills->kills ?? 0;
            $characterCrafts = $character->globalEventCrafts->crafts ?? 0;
        }

        return [
            'event_goals' => [
                'max_kills'                => $globalEventGoal->max_kills,
                'max_crafts'               => $globalEventGoal->max_crafts,
                'total_kills'              => $globalEventGoal->total_kills,
                'reward_every'             => $globalEventGoal->reward_every,
                'amount_needed_for_reward' => $this->fetchAmountNeeded($globalEventGoal),
                'current_kills'            => $characterKills,
                'current_crafts'           => $characterCrafts,
            ]
        ];
    }

    /**
     * Fetch the amount needed to gain reward.
     *
     * @param GlobalEventGoal $globalEventGoal
     * @return integer
     */
    public function fetchAmountNeeded(GlobalEventGoal $globalEventGoal): int {
        $participationAmount = $globalEventGoal->reward_every;

        $participants = $globalEventGoal->globalEventParticipation()->count();

        if ($participants > 0) {
            $participationAmount = round(($participationAmount / $participants));
        }

        return $participationAmount;
    }
}
