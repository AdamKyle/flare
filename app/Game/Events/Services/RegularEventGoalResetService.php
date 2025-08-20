<?php

namespace App\Game\Events\Services;

use App\Flare\Models\GlobalEventGoal;

class RegularEventGoalResetService
{
    /**
     * Reset a non-step-based goal and clear progress rows.
     *
     * @param GlobalEventGoal $goal
     * @return void
     */
    public function reset(GlobalEventGoal $goal): void
    {
        $goal->update([
            'next_reward_at' => $goal->reward_every,
        ]);

        $goal->globalEventParticipation()->delete();
        $goal->globalEventKills()->delete();
    }
}
