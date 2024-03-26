<?php

namespace App\Game\Skills\Handlers;

use Exception;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\Item;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class UpdateCraftingTasksForFactionLoyalty {

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
     * @var bool $handedOverItem
     */
    private bool $handedOverItem = false;

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param FactionLoyaltyService $factionLoyaltyService
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator, FactionLoyaltyService $factionLoyaltyService) {
        $this->randomAffixGenerator  = $randomAffixGenerator;
        $this->factionLoyaltyService = $factionLoyaltyService;
    }

    /**
     * Have we handed over the item?
     *
     * In reality: Have we updated the fame task for the npc?
     *
     * @return bool
     */
    public function handedOverItem(): bool {
        return $this->handedOverItem;
    }

    /**
     * Handle when we craft for a npc.
     *
     * @param Character $character
     * @param Item $item
     * @return Character
     * @throws Exception
     */
    public function handleCraftingTask(Character $character, Item $item): Character {

        $factionLoyalty = $this->getFactionLoyalty($character);

        if (is_null($factionLoyalty)) {
            return $character;
        }

        $helpingNpc = $this->getNpcCurrentlyHelping($factionLoyalty);

        if (is_null($helpingNpc)) {
            return $character;
        }

        if (!$this->hasMatchingTask($helpingNpc, 'item_id', $item->id)) {
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
            ServerMessageHandler::sendBasicMessage($character->user, $helpingNpc->npc->real_name . ' is elated at your ability to craft: ' . $item->affix_name . '. "Thank you child! Only: '.$amountLeft.' Left to go!"');
        }

        $canGainLevel = $this->canLevelUpFame($helpingNpc) && $helpingNpc->current_level !== $helpingNpc->max_level;



        if ($this->canLevelUpFame($helpingNpc) && $helpingNpc->current_level !== $helpingNpc->max_level) {
            $this->handleFameLevelUp($character, $helpingNpc);
        }

        return $character->refresh();
    }

    /**
     * Handle when the fame levels up.
     *
     * @param Character $character
     * @param FactionLoyaltyNpc $helpingNpc
     * @return void
     * @throws Exception
     */
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

    /**
     * Handle currencies.
     *
     * @param Character $character
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @return void
     */
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

    /**
     * handout XP
     *
     * @param Character $character
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @return void
     */
    protected function handOutXp(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): void {

        $newAmount = $factionLoyaltyNpc->current_level * 1000;

        $character->update([
            'xp' => $character->xp +  ($newAmount > 0 ? $newAmount : 1000),
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);

        ServerMessageHandler::sendBasicMessage($character->user, 'Rewarded with: ' . number_format($factionLoyaltyNpc->current_level * 1000) . ' XP.');
    }

    /**
     * Reward with a unique item.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
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

    /**
     * Can we level the fame of the npc we are helping, up?
     *
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @return bool
     */
    protected function canLevelUpFame(FactionLoyaltyNpc $factionLoyaltyNpc): bool {
        return $factionLoyaltyNpc->current_fame >= $factionLoyaltyNpc->next_level_fame;
    }
}
