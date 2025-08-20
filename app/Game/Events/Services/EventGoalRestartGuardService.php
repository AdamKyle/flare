<?php

namespace App\Game\Events\Services;

use App\Flare\Models\GlobalEventGoal;

class EventGoalRestartGuardService
{
    /**
     * Decide if the goal is complete and should restart.
     *
     * @param GlobalEventGoal $goal
     * @return bool
     */
    public function shouldRestart(GlobalEventGoal $goal): bool
    {
        if (!is_null($goal->max_kills) && $goal->total_kills < $goal->max_kills) {
            return false;
        }

        if (!is_null($goal->max_crafts) && $goal->total_crafts < $goal->max_crafts) {
            return false;
        }

        if (!is_null($goal->max_enchants) && $goal->total_enchants < $goal->max_enchants) {
            return false;
        }

        return true;
    }
}
