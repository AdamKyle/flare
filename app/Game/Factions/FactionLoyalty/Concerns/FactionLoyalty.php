<?php

namespace App\Game\Factions\FactionLoyalty\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty as FactionLoyaltyModel;
use App\Flare\Models\FactionLoyaltyNpc;

trait FactionLoyalty {

    /**
     * Get faction loyalty that the character is pledged to.
     *
     * @param Character $character
     * @return FactionLoyaltyModel|null
     */
    public function getFactionLoyalty(Character $character): ?FactionLoyaltyModel  {
       return $character->factionLoyalties()->where('is_pledged', true)->first();
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
        return collect($helpingNpc->factionLoyaltyNpcTasks->fame_tasks)->filter(function($task) use ($key, $id) {
            return isset($task[$key]) && $task[$key] === $id;
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
        $tasks = array_map(function ($task) use ($key, $id) {
            return isset($task[$key]) && ($task[$key] === $id) ?
                array_merge($task, ['current_amount' => min($task['current_amount'] + 1, $task['required_amount'])]) :
                $task;
        }, $helpingNpc->factionLoyaltyNpcTasks->fame_tasks);

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

        return optional(
            optional($character->factionLoyalties()->where('is_pledged', true)->first())
                ->factionLoyaltyNpcs()->where('currently_helping', true)->first()
        )->factionLoyaltyNpcTasks->fame_tasks
            ? collect(optional($character->factionLoyalties()->where('is_pledged', true)->first())
                ->factionLoyaltyNpcs()->where('currently_helping', true)->first()
                ->factionLoyaltyNpcTasks->fame_tasks)->contains('type', $craftingType)
            : false;
    }
}
