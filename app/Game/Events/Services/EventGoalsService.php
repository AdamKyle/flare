<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Core\Traits\ResponseBuilder;

class EventGoalsService
{
    use ResponseBuilder;

    public function fetchCurrentEventGoal(Character $character): array
    {
        return $this->successResult($this->getEventGoalData($character));
    }

    public function getEventGoalData(Character $character, ?GlobalEventGoal $goal = null, ?int $participantsCount = null): array
    {
        $globalEventGoal = $goal ?? GlobalEventGoal::first();
        $characterKills = 0;
        $characterCrafts = 0;
        $characterEnchants = 0;

        if (! is_null($character->globalEventKills) || ! is_null($character->globalEventCrafts) || ! is_null($character->globalEventEnchants)) {
            $characterKills = $character->globalEventKills->kills ?? 0;
            $characterCrafts = $character->globalEventCrafts->crafts ?? 0;
            $characterEnchants = $character->globalEventEnchants->enchants ?? 0;
        }

        return [
            'event_goals' => [
                'max_kills' => $globalEventGoal->max_kills,
                'max_crafts' => $globalEventGoal->max_crafts,
                'max_enchants' => $globalEventGoal->max_enchants,
                'total_kills' => $globalEventGoal->total_kills,
                'total_crafts' => $globalEventGoal->total_crafts,
                'total_enchants' => $globalEventGoal->total_enchants,
                'reward_every' => $globalEventGoal->reward_every,
                'amount_needed_for_reward' => $this->fetchAmountNeeded($globalEventGoal, $participantsCount),
                'reward' => $globalEventGoal->item_specialty_type_reward,
                'current_kills' => $characterKills,
                'current_crafts' => $characterCrafts,
                'current_enchants' => $characterEnchants,
                'should_be_mythic' => $globalEventGoal->should_be_mythic,
                'should_be_unique' => $globalEventGoal->should_be_unique,
            ],
        ];
    }

    public function getEventGoalDataFromNumbers(Character $character, GlobalEventGoal $goal, ?int $participantsCount, int $kills, int $crafts, int $enchants): array
    {
        return [
            'event_goals' => [
                'max_kills' => $goal->max_kills,
                'max_crafts' => $goal->max_crafts,
                'max_enchants' => $goal->max_enchants,
                'total_kills' => $goal->total_kills,
                'total_crafts' => $goal->total_crafts,
                'total_enchants' => $goal->total_enchants,
                'reward_every' => $goal->reward_every,
                'amount_needed_for_reward' => $this->fetchAmountNeeded($goal, $participantsCount),
                'reward' => $goal->item_specialty_type_reward,
                'current_kills' => $kills,
                'current_crafts' => $crafts,
                'current_enchants' => $enchants,
                'should_be_mythic' => $goal->should_be_mythic,
                'should_be_unique' => $goal->should_be_unique,
            ],
        ];
    }

    public function fetchAmountNeeded(GlobalEventGoal $globalEventGoal, ?int $precomputedParticipants = null): int
    {
        $participationAmount = $globalEventGoal->reward_every;

        $participants = $precomputedParticipants ?? $globalEventGoal->globalEventParticipation()->count();

        if ($participants > 0) {
            $participationAmount = round($participationAmount / $participants);
        }

        return $participationAmount;
    }
}
