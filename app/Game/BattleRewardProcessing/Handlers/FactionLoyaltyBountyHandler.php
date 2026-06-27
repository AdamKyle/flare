<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Services\FactionLoyaltyRewardRequestService;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class FactionLoyaltyBountyHandler
{
    use FactionLoyalty;

    public function __construct(
        private readonly FactionLoyaltyService $factionLoyaltyService,
        private readonly FactionLoyaltyRewardRequestService $factionLoyaltyRewardRequestService,
    ) {}

    /**
     * Handle the faction loyalty bounty.
     */
    public function handleBounty(Character $character, Monster $monster, int $killCount = 1): Character
    {
        $faction = $character->factions->where('game_map_id', $monster->game_map_id)->first();

        if (is_null($faction)) {
            return $character;
        }

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

        if ($helpingNpc->npc->gameMap->id !== $character->map->game_map_id) {
            return $character;
        }

        if (! $this->hasMatchingTask($helpingNpc, 'monster_id', $monster->id)) {
            return $character;
        }

        $helpingNpc = $this->updateMatchingHelpTask($helpingNpc, 'monster_id', $monster->id, $killCount);

        if ($this->wasCurrentFameForTaskUpdated()) {
            $matchingTask = $this->getMatchingTask($helpingNpc, 'monster_id', $monster->id);

            ServerMessageHandler::sendBasicMessage($character->user, $helpingNpc->npc->real_name .
                ' is happy that you slaughtered another one of the enemies on their hit list. "Only: ' .
                ($matchingTask['required_amount'] - $matchingTask['current_amount']) . ' to go child!"');
        }

        while ($this->canLevelUpFame($helpingNpc)) {
            $this->handleFameLevelUp($character, $helpingNpc);

            $helpingNpc = $helpingNpc->refresh();
        }

        return $character->refresh();
    }

    private function handleFameLevelUp(Character $character, FactionLoyaltyNpc $helpingNpc): void
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

    private function canLevelUpFame(FactionLoyaltyNpc $factionLoyaltyNpc): bool
    {
        if ($factionLoyaltyNpc->current_level >= $factionLoyaltyNpc->max_level) {
            return false;
        }

        return $factionLoyaltyNpc->current_fame >= $factionLoyaltyNpc->next_level_fame;
    }

    private function normalizeMaxLevelNpc(FactionLoyaltyNpc $factionLoyaltyNpc): bool
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
