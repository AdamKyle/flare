<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
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
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class FactionLoyaltyBountyHandler {

    use HandleCharacterLevelUp, FactionLoyalty;

    /**
     * @var RandomAffixGenerator $randomAffixGenerator
     */
    private RandomAffixGenerator $randomAffixGenerator;

    /**
     * @var FactionLoyaltyService $factionLoyaltyService
     */
    private FactionLoyaltyService $factionLoyaltyService;

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param FactionLoyaltyService $factionLoyaltyService
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator, FactionLoyaltyService $factionLoyaltyService) {
        $this->randomAffixGenerator = $randomAffixGenerator;
        $this->factionLoyaltyService = $factionLoyaltyService;
    }

    /**
     * Handle the faction loyalty bounty.
     *
     * @param Character $character
     * @param Monster $monster
     * @return Character
     */
    public function handleBounty(Character $character, Monster $monster): Character {

        if ($character->currentAutomations->isNotEmpty()) {
             return $character;
        }

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

        if (!$this->hasMatchingTask($helpingNpc, 'monster_id', $monster->id)) {
            return $character;
        }

        $helpingNpc = $this->updateMatchingHelpTask($helpingNpc, 'monster_id', $monster->id);

        if ($this->wasCurrentFameForTaskUpdated()) {

            $matchingTask = $this->getMatchingTask($helpingNpc, 'monster_id', $monster->id);

            ServerMessageHandler::sendBasicMessage($character->user, $helpingNpc->npc->real_name .
                ' is happy that you slaughtered another one of the enemies on their hit list. "Only: ' .
                ($matchingTask['required_amount'] - $matchingTask['current_amount']).' to go child!"');
        }

        if ($this->canLevelUpFame($helpingNpc) && $helpingNpc->current_level !== $helpingNpc->max_level) {
            $this->handleFameLevelUp($character, $helpingNpc);
        }

        return $character->refresh();
    }

    protected function handleFameLevelUp(Character $character, FactionLoyaltyNpc $helpingNpc): void {

        $this->handOutXp($character, $helpingNpc);
        $this->handOutCurrencies($character, $helpingNpc);
        $this->rewardTheUniqueItem($character);

        $newLevel = $helpingNpc->current_level + 1;

        if ($newLevel >= $helpingNpc->max_level) {
            $newLevel = $helpingNpc->max_level;
        }

        $helpingNpc->update([
            'current_level'  => $newLevel,
        ]);

        $helpingNpc = $helpingNpc->refresh();

        if ($helpingNpc->current_level === $helpingNpc->max_level) {
            $helpingNpc->factionLoyaltyNpcTasks->update([
                'fame_tasks' => []
            ]);
        } else {
            $this->factionLoyaltyService->createNewTasksForNpc($helpingNpc->factionLoyaltyNpcTasks);
        }
    }

    protected function handOutCurrencies(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): void {
        $newGold     = (($factionLoyaltyNpc->current_level <= 0 ? 1 : $factionLoyaltyNpc->current_level) * 1000000) + $character->gold;
        $newGoldDust = (($factionLoyaltyNpc->current_level <= 0 ? 1 : $factionLoyaltyNpc->current_level) * 1000) + $character->gold_dust;
        $newShards   = (($factionLoyaltyNpc->current_level <= 0 ? 1 : $factionLoyaltyNpc->current_level) * 100) + $character->shards;

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

        event(new UpdateTopBarEvent($character->refresh()));

        ServerMessageHandler::sendBasicMessage($character->user, 'Your fame with: ' . $factionLoyaltyNpc->npc->real_name . ' on Plane: ' . $factionLoyaltyNpc->npc->gameMap->name);
    }

    protected function handOutXp(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): void {

        $newXp = 0;

        if ($factionLoyaltyNpc->current_level <= 0) {
            $newXp += 1000;
        } else {
            $newXp += $factionLoyaltyNpc->current_level * 1000;
        }

        $character->update([
            'xp' => $character->xp + $newXp
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);

        ServerMessageHandler::sendBasicMessage($character->user, 'Rewarded with: ' . number_format($newXp) . ' XP.');
    }

    protected function rewardTheUniqueItem(Character $character) {
        $item = Item::whereNull('specialty_type')
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket', 'quest'])
            ->inRandomOrder()
            ->first();

        $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::MEDIUM);

        $newItem = $item->duplicate();

        $newItem->update([
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
        ]);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $newItem->id,
        ]);

        event(new ServerMessageEvent($character->user, 'You found something of MEDIUM value child. A simple reward: ' . $item->affix_name, $slot->id));
    }

    protected function canLevelUpFame(FactionLoyaltyNpc $factionLoyaltyNpc): bool {
        if ($factionLoyaltyNpc->current_level >= $factionLoyaltyNpc->max_level) {
            return false;
        }

        return $factionLoyaltyNpc->current_fame >= $factionLoyaltyNpc->next_level_fame;
    }
}
