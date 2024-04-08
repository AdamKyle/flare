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
        $characterEnchants = 0;

        if (!is_null($character->globalEventKills) || !is_null($character->globalEventCrafts) || !is_null($character->globalEventEnchants)) {
            $characterKills = $character->globalEventKills->kills ?? 0;
            $characterCrafts = $character->globalEventCrafts->crafts ?? 0;
            $characterEnchants = $character->globalEventEnchants->enchants ?? 0;
        }

        return [
            'event_goals' => [
                'max_kills'                => $globalEventGoal->max_kills,
                'max_crafts'               => $globalEventGoal->max_crafts,
                'max_enchants'             => $globalEventGoal->max_enchants,
                'total_kills'              => $globalEventGoal->total_kills,
                'total_crafts'             => $globalEventGoal->total_crafts,
                'total_enchants'           => $globalEventGoal->total_enchants,
                'reward_every'             => $globalEventGoal->reward_every,
                'amount_needed_for_reward' => $this->fetchAmountNeeded($globalEventGoal),
                'reward'                   => $globalEventGoal->item_specialty_type_reward,
                'current_kills'            => $characterKills,
                'current_crafts'           => $characterCrafts,
                'current_enchants'         => $characterEnchants,
                'should_be_mythic'         => $globalEventGoal->should_be_mythic,
                'should_be_unique'         => $globalEventGoal->should_be_unique,
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
