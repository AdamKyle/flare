<?php

namespace App\Game\Events\Services;


use App\Game\Core\Traits\ResponseBuilder;
use App\Flare\Models\GlobalEventGoal;


class EventGoalsService {

    use ResponseBuilder;

    /**
     * Fetches the current event goal data for controller action.
     *
     * @return array
     */
    public function fetchCurrentEventGoal(): array {
        return $this->successResult($this->getEventGoalData());
    }

    /**
     * Get the event goal data.
     *
     * @return array
     */
    public function getEventGoalData(): array {
        $globalEventGoal = GlobalEventGoal::first();

        return [
            'event_goals' => [
                'max_kills'               => $globalEventGoal->max_kills,
                'total_kills'             => $globalEventGoal->total_kills,
                'reward_every'            => $globalEventGoal->reward_every_kills,
                'kills_needed_for_reward' => $this->fetchKillAmountNeeded($globalEventGoal),
            ]
        ];
    }

    /**
     * Fetch the amount needed to gain reward.
     *
     * @param GlobalEventGoal $globalEventGoal
     * @return integer
     */
    public function fetchKillAmountNeeded(GlobalEventGoal $globalEventGoal): int {
        $participationAmount = $globalEventGoal->reward_every_kills;

        $participants = $globalEventGoal->globalEventParticipation()->count();

        if ($participants > 0) {
            $participationAmount = round(($participationAmount / $participants));
        }

        return $participationAmount;
    }
}
