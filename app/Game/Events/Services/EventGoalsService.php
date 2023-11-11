<?php

namespace App\Game\Events\Services;


use App\Game\Core\Traits\ResponseBuilder;
use App\Flare\Models\GlobalEventGoal;


class EventGoalsService {

    use ResponseBuilder;

    public function fetchCurrentEventGoal(): array {

        $globalEventGoal = GlobalEventGoal::first();

        return $this->successResult([
            'event_goals' => [
                'max_kills'    => $globalEventGoal->max_kills,
                'total_kills'  => $globalEventGoal->total_kills,
                'reward_every' => $globalEventGoal->reward_every_kills,
            ]
        ]);
    }
}
