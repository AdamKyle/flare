<?php

namespace App\Game\Events\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;

trait UpdateCharacterEventGoalParticipation
{
    /**
     * Handle updating participation for an event goal.
     */
    public function handleUpdatingParticipation(Character $character, GlobalEventGoal $globalEventGoal, string $attribute, int $amount = 1): void
    {

        $globalEventParticipation = $character->globalEventParticipation;

        if (is_null($globalEventParticipation)) {

            $character->globalEventParticipation()->create([
                'global_event_goal_id' => $globalEventGoal->id,
                'character_id' => $character->id,
                'current_'.$attribute => $amount,
            ]);

            if ($attribute === 'kills') {
                $character->globalEventKills()->create([
                    'global_event_goal_id' => $globalEventGoal->id,
                    'character_id' => $character->id,
                    'kills' => $amount,
                ]);
            }

            if ($attribute === 'crafts') {
                $character->globalEventCrafts()->create([
                    'global_event_goal_id' => $globalEventGoal->id,
                    'character_id' => $character->id,
                    'crafts' => $amount,
                ]);
            }

            if ($attribute === 'enchants') {
                $character->globalEventEnchants()->create([
                    'global_event_goal_id' => $globalEventGoal->id,
                    'character_id' => $character->id,
                    'enchants' => $amount,
                ]);
            }

            return;
        }

        if ($attribute === 'crafts') {
            $character->globalEventParticipation()->update([
                'current_crafts' => $character->globalEventParticipation->current_crafts + $amount,
            ]);

            $character->globalEventCrafts()->update([
                'crafts' => $character->globalEventCrafts->crafts + $amount,
            ]);

            return;
        }

        if ($attribute === 'enchants') {
            $character->globalEventParticipation()->update([
                'current_enchants' => $character->globalEventParticipation->current_enchants + $amount,
            ]);

            $character->globalEventEnchants()->update([
                'enchants' => $character->globalEventEnchants->enchants + $amount,
            ]);

            return;
        }

        $character->globalEventParticipation()->update([
            'current_kills' => $character->globalEventParticipation->current_kills + $amount,
        ]);

        $character->globalEventKills()->update([
            'kills' => $character->globalEventKills->kills + $amount,
        ]);
    }
}
