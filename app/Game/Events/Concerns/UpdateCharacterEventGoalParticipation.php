<?php

namespace App\Game\Events\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;

trait UpdateCharacterEventGoalParticipation {

    /**
     *
     * Handle updating participation for an event goal.
     *
     * @param Character $character
     * @param GlobalEventGoal $globalEventGoal
     * @param string $attribute
     * @return void
     */
    public function handleUpdatingParticipation(Character $character, GlobalEventGoal $globalEventGoal, string $attribute): void {
        $globalEventParticipation = $character->globalEventParticipation;

        if (is_null($globalEventParticipation)) {
            $character->globalEventParticipation()->create([
                'global_event_goal_id'   => $globalEventGoal->id,
                'character_id'           => $character->id,
                'current_' . $attribute  => 1,
            ]);

            if ($attribute === 'kills') {
                $character->globalEventKills()->create([
                    'global_event_goal_id' => $globalEventGoal->id,
                    'character_id' => $character->id,
                    'kills' => 1,
                ]);
            }

            if ($attribute === 'crafts') {
                $character->globalEventKills()->create([
                    'global_event_goal_id' => $globalEventGoal->id,
                    'character_id' => $character->id,
                    'crafts' => 1,
                ]);
            }

            return;
        }

        $character->globalEventParticipation()->update([
            'current_kills' => $character->globalEventParticipation->{'current_' . $attribute} + 1,
        ]);

        $character->globalEventKills()->update([
            'kills' => $character->globalEventKills->{$attribute} + 1,
        ]);

    }
}
