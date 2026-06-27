<?php

namespace App\Game\Skills\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\Item;
use App\Game\BattleRewardProcessing\Services\FactionLoyaltyRewardRequestService;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class UpdateCraftingTasksForFactionLoyalty
{
    use FactionLoyalty;

    private FactionLoyaltyService $factionLoyaltyService;

    private FactionLoyaltyRewardRequestService $factionLoyaltyRewardRequestService;

    private bool $handedOverItem = false;

    public function __construct(
        FactionLoyaltyService $factionLoyaltyService,
        FactionLoyaltyRewardRequestService $factionLoyaltyRewardRequestService,
    ) {
        $this->factionLoyaltyService = $factionLoyaltyService;
        $this->factionLoyaltyRewardRequestService = $factionLoyaltyRewardRequestService;
    }

    /**
     * Have we handed over the item?
     *
     * In reality: Have we updated the fame task for the npc?
     */
    public function handedOverItem(): bool
    {
        return $this->handedOverItem;
    }

    /**
     * Handle when we craft for a npc.
     */
    public function handleCraftingTask(Character $character, Item $item): Character
    {
        $factionLoyalty = $this->getFactionLoyalty($character);

        if (is_null($factionLoyalty)) {
            return $character;
        }

        $helpingNpc = $this->getNpcCurrentlyHelping($factionLoyalty);

        if (is_null($helpingNpc)) {
            return $character;
        }

        if ($this->normalizeMaxLevelNpc($helpingNpc)) {
            return $character;
        }

        if (! $this->hasMatchingTask($helpingNpc, 'item_id', $item->id)) {
            return $character;
        }

        $this->handedOverItem = true;

        $helpingNpc = $this->updateMatchingHelpTask($helpingNpc, 'item_id', $item->id);

        $helpingNpc = $helpingNpc->refresh();

        $task = $this->getMatchingTask($helpingNpc, 'item_id', $item->id);

        $amountLeft = $task['required_amount'] - $task['current_amount'];

        if ($amountLeft === 0) {
            ServerMessageHandler::sendBasicMessage($character->user, $helpingNpc->npc->real_name . ' does not want anymore of this item anymore. "We\'re done with this child. Move on. I got other tasks for you to do! But you since you crafted it ..."');
        } else {
            ServerMessageHandler::sendBasicMessage($character->user, $helpingNpc->npc->real_name . ' is elated at your ability to craft: ' . $item->affix_name . '. "Thank you child! Only: ' . $amountLeft . ' Left to go!"');
        }

        if ($this->canLevelUpFame($helpingNpc)) {
            $this->handleFameLevelUp($character, $helpingNpc);
        }

        return $character->refresh();
    }

    /**
     * Handle when the fame levels up.
     */
    protected function handleFameLevelUp(Character $character, FactionLoyaltyNpc $helpingNpc): void
    {
        if ($this->normalizeMaxLevelNpc($helpingNpc)) {
            return;
        }

        $rewardLevel = $helpingNpc->current_level;
        $newLevel = min($helpingNpc->current_level + 1, $helpingNpc->max_level);

        $helpingNpc->update(['current_level' => $newLevel]);
        $helpingNpc = $helpingNpc->refresh();

        if ($helpingNpc->current_level === $helpingNpc->max_level) {
            $helpingNpc->factionLoyaltyNpcTasks->update(['fame_tasks' => []]);
        } else {
            $this->factionLoyaltyService->createNewTasksForNpc($helpingNpc->factionLoyaltyNpcTasks, $character);
        }

        $normalizedLevel = $rewardLevel <= 0 ? 1 : $rewardLevel;

        $this->factionLoyaltyRewardRequestService->enqueue(
            $character->id,
            $helpingNpc->id,
            $rewardLevel,
            [
                'faction_loyalty_npc_id' => $helpingNpc->id,
                'npc_id' => $helpingNpc->npc_id,
                'npc_name' => $helpingNpc->npc->real_name,
                'game_map_name' => $helpingNpc->npc->gameMap->name,
                'reward_level' => $rewardLevel,
                'new_fame_level' => $newLevel,
                'max_level' => $helpingNpc->max_level,
                'xp_amount' => $rewardLevel <= 0 ? 1000 : $rewardLevel * 1000,
                'gold_amount' => $normalizedLevel * 1_000_000,
                'gold_dust_amount' => $normalizedLevel * 1_000,
                'shards_amount' => $normalizedLevel * 100,
            ]
        );
    }

    /**
     * Can we level the fame of the npc we are helping, up?
     */
    protected function canLevelUpFame(FactionLoyaltyNpc $factionLoyaltyNpc): bool
    {
        if ($factionLoyaltyNpc->current_level >= $factionLoyaltyNpc->max_level) {
            return false;
        }

        return $factionLoyaltyNpc->current_fame >= $factionLoyaltyNpc->next_level_fame;
    }

    protected function normalizeMaxLevelNpc(FactionLoyaltyNpc $factionLoyaltyNpc): bool
    {
        if ($factionLoyaltyNpc->current_level < $factionLoyaltyNpc->max_level) {
            return false;
        }

        if ($factionLoyaltyNpc->current_level !== $factionLoyaltyNpc->max_level) {
            $factionLoyaltyNpc->update([
                'current_level' => $factionLoyaltyNpc->max_level,
            ]);
        }

        if (! is_null($factionLoyaltyNpc->factionLoyaltyNpcTasks)) {
            $factionLoyaltyNpc->factionLoyaltyNpcTasks->update([
                'fame_tasks' => [],
            ]);
        }

        return true;
    }
}
