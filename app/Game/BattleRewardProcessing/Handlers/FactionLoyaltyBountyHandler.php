<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class FactionLoyaltyBountyHandler
{
    use FactionLoyalty, HandleCharacterLevelUp;

    public function __construct(
        private RandomAffixGenerator $randomAffixGenerator,
        private FactionLoyaltyService $factionLoyaltyService,
        private BattleMessageHandler $battleMessageHandler
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

        if ($helpingNpc->npc->gameMap->id !== $character->map->game_map_id) {
            return $character;
        }

        if (! $this->hasMatchingTask($helpingNpc, 'monster_id', $monster->id)) {
            return $character;
        }

        $helpingNpc = $this->updateMatchingHelpTask($helpingNpc, 'monster_id', $monster->id, $killCount);

        if ($this->wasCurrentFameForTaskUpdated()) {
            $matchingTask = $this->getMatchingTask($helpingNpc, 'monster_id', $monster->id);

            ServerMessageHandler::sendBasicMessage($character->user, $helpingNpc->npc->real_name.
                ' is happy that you slaughtered another one of the enemies on their hit list. "Only: '.
                ($matchingTask['required_amount'] - $matchingTask['current_amount']).' to go child!"');
        }

        while ($this->canLevelUpFame($helpingNpc) && $helpingNpc->current_level !== $helpingNpc->max_level) {
            $this->handleFameLevelUp($character, $helpingNpc);

            $helpingNpc = $helpingNpc->refresh();
        }

        return $character->refresh();
    }

    private function handleFameLevelUp(Character $character, FactionLoyaltyNpc $helpingNpc): void
    {
        $newLevel = $helpingNpc->current_level + 1;
        $helpingNpcName = $helpingNpc->npc->real_name;

        ServerMessageHandler::sendBasicMessage(
            $character->user,
            'Your fame with: '.$helpingNpc->npc->real_name.
                ' on Plane: '.$helpingNpc->npc->gameMap->name.
                ' is now level: '.$helpingNpc->current_level.
                ' out of: '.$helpingNpc->max_level.'. You also got some XP and other rewards!'
        );

        $this->handOutXp($character, $helpingNpc, $newLevel, $helpingNpcName);
        $this->handOutCurrencies($character, $helpingNpc);
        $this->rewardTheUniqueItem($character);

        if ($newLevel >= $helpingNpc->max_level) {
            $newLevel = $helpingNpc->max_level;
        }

        $helpingNpc->update([
            'current_level' => $newLevel,
        ]);

        $helpingNpc = $helpingNpc->refresh();

        if ($helpingNpc->current_level === $helpingNpc->max_level) {
            $helpingNpc->factionLoyaltyNpcTasks->update([
                'fame_tasks' => [],
            ]);
        } else {
            $this->factionLoyaltyService->createNewTasksForNpc($helpingNpc->factionLoyaltyNpcTasks, $character);
        }
    }

    private function handOutCurrencies(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): void
    {
        $goldToReward = (($factionLoyaltyNpc->current_level <= 0 ? 1 : $factionLoyaltyNpc->current_level) * 1_000_000);
        $goldDustToReward = (($factionLoyaltyNpc->current_level <= 0 ? 1 : $factionLoyaltyNpc->current_level) * 1_000);
        $shardsToReward = (($factionLoyaltyNpc->current_level <= 0 ? 1 : $factionLoyaltyNpc->current_level) * 1_00);

        $newGold = $goldToReward + $character->gold;
        $newGoldDust = $goldDustToReward + $character->gold_dust;
        $newShards = $shardsToReward + $character->shards;

        if ($newGold >= MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        if ($newGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $newGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        if ($newShards >= MaxCurrenciesValue::MAX_SHARDS) {
            $newShards = MaxCurrenciesValue::MAX_SHARDS;
        }

        $character->update([
            'gold' => $newGold,
            'gold_dust' => $newGoldDust,
            'shards' => $newShards,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::GOLD, $goldToReward, $character->gold);
        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::GOLD_DUST, $goldDustToReward, $character->gold_dust);
        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::SHARDS, $shardsToReward, $character->shards);
    }

    private function handOutXp(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc, int $newLevel, string $helpingNpcName): void
    {

        $newXp = 0;

        if ($factionLoyaltyNpc->current_level <= 0) {
            $newXp += 1000;
        } else {
            $newXp += $factionLoyaltyNpc->current_level * 1000;
        }

        $character->update([
            'xp' => $character->xp + $newXp,
        ]);

        $character = $character->refresh();

        $this->battleMessageHandler->handleFactionLoyaltyXp($character->user, $newXp, $newLevel, $helpingNpcName);

        $this->handlePossibleLevelUp($character);
    }

    private function rewardTheUniqueItem(Character $character)
    {
        $item = Item::whereNull('specialty_type')
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket', 'quest'])
            ->inRandomOrder()
            ->first();

        $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::LEGENDARY);

        $newItem = $item->duplicate();

        $newItem->update([
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
        ]);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $newItem->id,
        ]);

        event(new ServerMessageEvent($character->user, 'You found something of Unique child: '.$item->affix_name, $slot->id));
    }

    private function canLevelUpFame(FactionLoyaltyNpc $factionLoyaltyNpc): bool
    {
        if ($factionLoyaltyNpc->current_level >= $factionLoyaltyNpc->max_level) {
            return false;
        }

        return $factionLoyaltyNpc->current_fame >= $factionLoyaltyNpc->next_level_fame;
    }
}
