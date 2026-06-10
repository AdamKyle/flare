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
        $globalEventParticipation = $character->globalEventParticipation()
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->first();

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
            $newTotal = $globalEventParticipation->current_crafts + $amount;

            $character->globalEventParticipation()
                ->where('global_event_goal_id', $globalEventGoal->id)
                ->update(['current_crafts' => $newTotal]);

            $craftsRow = $character->globalEventCrafts()
                ->where('global_event_goal_id', $globalEventGoal->id)
                ->first();

            if (is_null($craftsRow)) {
                $character->globalEventCrafts()->create([
                    'global_event_goal_id' => $globalEventGoal->id,
                    'character_id' => $character->id,
                    'crafts' => $newTotal,
                ]);

                return;
            }

            $character->globalEventCrafts()
                ->where('global_event_goal_id', $globalEventGoal->id)
                ->update(['crafts' => $newTotal]);

            return;
        }

        if ($attribute === 'enchants') {
            $newTotal = $globalEventParticipation->current_enchants + $amount;

            $character->globalEventParticipation()
                ->where('global_event_goal_id', $globalEventGoal->id)
                ->update(['current_enchants' => $newTotal]);

            $enchantsRow = $character->globalEventEnchants()
                ->where('global_event_goal_id', $globalEventGoal->id)
                ->first();

            if (is_null($enchantsRow)) {
                $character->globalEventEnchants()->create([
                    'global_event_goal_id' => $globalEventGoal->id,
                    'character_id' => $character->id,
                    'enchants' => $newTotal,
                ]);

                return;
            }

            $character->globalEventEnchants()
                ->where('global_event_goal_id', $globalEventGoal->id)
                ->update(['enchants' => $newTotal]);

            return;
        }

        $newTotal = $globalEventParticipation->current_kills + $amount;

        $character->globalEventParticipation()
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->update(['current_kills' => $newTotal]);

        $killsRow = $character->globalEventKills()
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->first();

        if (is_null($killsRow)) {
            $character->globalEventKills()->create([
                'global_event_goal_id' => $globalEventGoal->id,
                'character_id' => $character->id,
                'kills' => $newTotal,
            ]);

            return;
        }

        $character->globalEventKills()
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->update(['kills' => $newTotal]);
    }
}
