<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class FactionLoyaltyBountyHandler {

    use HandleCharacterLevelUp;

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

        $factionLoyalty = $character->factionLoyalties()->where('faction_id', $faction->id)->where('is_pledged', true)->first();

        if (is_null($factionLoyalty)) {
            return $character;
        }

        $helpingNpc = $factionLoyalty->factionLoyaltyNpcs->where('currently_helping', true)->first();

        if (is_null($helpingNpc)) {
             return $character;
        }

        $hasMonsterForBounty = collect($helpingNpc->factionLoyaltyNpcTasks->fame_tasks)->filter(function($task) use($monster) {
            if (!isset($task['monster_id'])) {
                return collect();
            }

            return $task['monster_id'] === $monster->id;
        })->isNotEmpty();

        if (!$hasMonsterForBounty) {
            return $character;
        }

        $tasks = collect($helpingNpc->factionLoyaltyNpcTasks->fame_tasks)->map(function($task) use($monster) {

            if (!isset($task['monster_id'])) {
                return $task;
            }

            if ($task['monster_id'] === $monster->id) {
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

        $helpingNpc = $helpingNpc->refresh();

        if ($this->canLevelUpFame($helpingNpc) && $helpingNpc->current_level !== $helpingNpc->max_level) {
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

        return $character->refresh();
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
        $character->update([
            'xp' => $character->xp + $factionLoyaltyNpc->current_level * 1000
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);

        ServerMessageHandler::sendBasicMessage($character->user, 'Rewarded with: ' . number_format($factionLoyaltyNpc->current_level * 1000) . ' XP.');
    }

    protected function rewardTheUniqueItem(Character $character) {
        $item = Item::whereNull('specialty_type')
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket'])
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
