<?php

namespace App\Game\Factions\FactionLoyalty\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty as FactionLoyaltyModel;
use App\Flare\Models\FactionLoyaltyNpc;

trait FactionLoyalty {

    /**
     * Get faction loyalty.
     *
     * @param Character $character
     * @param Faction $faction
     * @return FactionLoyaltyModel|null
     */
    public function getFactionLoyalty(Character $character, Faction $faction): ?FactionLoyaltyModel  {
       return $character->factionLoyalties()->where('faction_id', $faction->id)->where('is_pledged', true)->first();
    }

    /**
     * Get the current npc that the player is helping.
     *
     * @param FactionLoyaltyModel $factionLoyalty
     * @return FactionLoyaltyNpc|null
     */
    public function getNpcCurrentlyHelping(FactionLoyaltyModel $factionLoyalty): ?FactionLoyaltyNpc {
        return $factionLoyalty->factionLoyaltyNpcs->where('currently_helping', true)->first();
    }

    /**
     * Does the NPC have a matching task?
     *
     * @param FactionLoyaltyNpc $helpingNpc
     * @param string $key
     * @param int $id
     * @return bool
     */
    public function hasMatchingTask(FactionLoyaltyNpc $helpingNpc, string $key, int $id): bool {
        return collect($helpingNpc->factionLoyaltyNpcTasks->fame_tasks)->filter(function($task) use($key, $id) {
            if (!isset($task[$key])) {
                return collect();
            }

            return $task[$key] === $id;
        })->isNotEmpty();
    }

    /**
     * Updates a matching helping task.
     *
     * @param FactionLoyaltyNpc $helpingNpc
     * @param string $key
     * @param int $id
     * @return FactionLoyaltyNpc
     */
    public function updateMatchingHelpTask(FactionLoyaltyNpc $helpingNpc, string $key, int $id): FactionLoyaltyNpc {
        $tasks = collect($helpingNpc->factionLoyaltyNpcTasks->fame_tasks)->map(function($task) use($key, $id) {

            if (!isset($task[$key])) {
                return $task;
            }

            if ($task[$key] === $id) {
                $newCurrent = $task['current_amount'] + 1;

                if ($newCurrent > $task['required_amount']) {
                    $newCurrent = $task['required_amount'];
                }

                $task['current_amount'] = $newCurrent;
            }

            return $task;
        })->toArray();

        $helpingNpc->factionLoyaltyNpcTasks()->update([
            'fame_tasks' => $tasks
        ]);

        return $helpingNpc->refresh();
    }

    /**
     * Should we show the npc craft button?
     *
     * @param Character $character
     * @param string $craftingType
     * @return bool
     */
    public function showCraftForNpcButton(Character $character, string $craftingType): bool {
        $pledgedFaction = $character->factionLoyalties()->where('is_pledged', true)->first();

        if (is_null($pledgedFaction)) {
            return false;
        }

        $helpingNpc = $pledgedFaction->factionLoyaltyNpcs()->where('currently_helping', true)->first();

        if (is_null($helpingNpc)) {
            return false;
        }

        if (empty($helpingNpc->fame_tasks)) {
            return false;
        }

        return collect($helpingNpc->fame_tasks)->filter(function($task) use($craftingType) {
            if (!isset($task['type'])) {
                return collect();
            }

            return $task['type'] === $craftingType;
        })->isNotEmpty();
    }
}
