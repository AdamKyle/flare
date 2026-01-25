<?php

namespace App\Game\Battle\Services;

use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantService;
use Exception;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class BattleDrop
{
    use CanHaveQuestItem;

    private Monster $monster;

    private ?Location $locationWithEffect;

    private float $gameMapBonus;

    private float $lootingChance;

    /**
     * @param RandomItemDropBuilder $randomItemDropBuilder
     * @param DisenchantService $disenchantService
     * @param ShopService $shopService
     */
    public function __construct(
        private readonly RandomItemDropBuilder $randomItemDropBuilder,
        private readonly DisenchantService $disenchantService,
        private readonly ShopService $shopService) {}

    /**
     * Set Monster.
     */
    public function setMonster(Monster $monster): BattleDrop
    {
        $this->monster = $monster;

        return $this;
    }

    /**
     * Set Special Location.
     */
    public function setSpecialLocation(?Location $location = null): BattleDrop
    {
        $this->locationWithEffect = $location;

        return $this;
    }

    /**
     * Set Game Map Bonus.
     */
    public function setGameMapBonus(float $gameMapBonus = 0.0): BattleDrop
    {
        $this->gameMapBonus = $gameMapBonus;

        return $this;
    }

    /**
     * Set Location Chance.
     */
    public function setLootingChance(float $lootingChance = 0.0): BattleDrop
    {
        $this->lootingChance = $lootingChance;

        return $this;
    }

    /**
     * Handles fetching the drop for the player.
     *
     * If the player can get the drop we will handle all aspects including
     * attempting to pick up the drop.
     */
    public function handleDrop(Character $character, bool $canGetDrop, bool $returnItem = false): ?Item
    {
        if ($canGetDrop) {
            $drop = $this->getDropFromCache($character, $this->monster->gameMap->name, $this->locationWithEffect);

            if (! is_null($drop)) {
                if ((! is_null($drop->itemSuffix) || ! is_null($drop->itemPrefix)) && ! $returnItem) {
                    $this->attemptToPickUpItem($character, $drop);
                } else {
                    return $drop;
                }
            }
        }

        return null;
    }

    /**
     * Give player a mythical item.
     *
     * @return void
     */
    public function giveMythicItem(Character $character, Item $item)
    {
        $this->giveItemToPlayer($character, $item, true);
    }

    /**
     * Handles the monsters quest drop.
     *
     * Can return the item.
     */
    public function handleMonsterQuestDrop(Character $character, bool $returnItem = false): ?Item
    {
        if (! is_null($this->monster->quest_item_id)) {

            $canGetQuestItem = DropCheckCalculator::fetchQuestItemDropCheck($this->monster, $this->lootingChance, $this->gameMapBonus);

            if ($canGetQuestItem && ! $returnItem) {
                $this->attemptToPickUpItem($character, $this->monster->questItem);
            } elseif ($canGetQuestItem && $returnItem) {
                return $this->monster->questItem;
            }
        }

        return null;
    }

    /**
     * Handle when a character is in a dwelve exploration for quest items
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function handleDwelveLocationQuestItems(Character $character): void {
        $automation = $character->currentAutomations()->where('type', AutomationType::DWELVE)->first();

        if (is_null($automation)) {
            return;
        }

        $location = Location::where('type', LocationType::CAVE_OF_MEMORIES)
                            ->where('x', $character->map->character_position_x)
                            ->where('y', $character->map->character_position_y)
                            ->where('game_map_id', $character->map->game_map_id)
                            ->whereNotNull('hours_to_drop')
                            ->first();

        if (is_null($location)) {
            return;
        }

        if (now()->diffInHours($automation->started_at) < $location->hours_to_drop) {
            return;
        }

        $items = Item::where('drop_location_id', $location->id)
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->where('type', 'quest')->get();

        if ($items->isNotEmpty()) {
            $canHave = DropCheckCalculator::fetchDifficultItemChance($this->lootingChance, 100);

            if (! $canHave) {
                return;
            }

            $character->loadMissing('inventory.slots');

            $candidateItemIds = $items->pluck('id')->all();
            $ownedItemIds = $character->inventory->slots->pluck('item_id')->all();
            $ownedItemIdSet = array_fill_keys($ownedItemIds, true);

            $quests = Quest::query()
                ->where(function ($query) use ($candidateItemIds) {
                    $query->whereIn('item_id', $candidateItemIds)
                        ->orWhereIn('secondary_required_item', $candidateItemIds);
                })
                ->get();

            $questByItemId = [];

            foreach ($quests as $quest) {
                if (! is_null($quest->item_id) && in_array($quest->item_id, $candidateItemIds, true) && ! array_key_exists($quest->item_id, $questByItemId)) {
                    $questByItemId[$quest->item_id] = $quest->id;
                }

                if (! is_null($quest->secondary_required_item) && in_array($quest->secondary_required_item, $candidateItemIds, true) && ! array_key_exists($quest->secondary_required_item, $questByItemId)) {
                    $questByItemId[$quest->secondary_required_item] = $quest->id;
                }
            }

            $completedQuestIds = $character->questsCompleted()->pluck('quest_id')->all();
            $completedQuestIdSet = array_fill_keys($completedQuestIds, true);

            $eligibleItems = $items->filter(function (Item $item) use ($ownedItemIdSet, $questByItemId, $completedQuestIdSet): bool {
                $doesntHave = ! array_key_exists($item->id, $ownedItemIdSet);

                $questId = $questByItemId[$item->id] ?? null;

                if (! is_null($questId)) {
                    $isCompleted = array_key_exists($questId, $completedQuestIdSet);

                    return ! $isCompleted && $doesntHave;
                }

                return $doesntHave;
            });

            if ($eligibleItems->isNotEmpty()) {
                $this->attemptToPickUpItem($character, $eligibleItems->random());
            }
        }
    }

    /**
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function handleSpecialLocationQuestItem(Character $character): void
    {
        $automation = $character->currentAutomations()->where('type', AutomationType::EXPLORING)->first();

        if (! is_null($automation)) {
            return;
        }

        $lootingChance = $this->lootingChance > 0.45 ? 0.45 : $this->lootingChance;

        $items = Item::where('drop_location_id', $this->locationWithEffect->id)
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->where('type', 'quest')->get();

        if ($items->isNotEmpty()) {
            $canHave = DropCheckCalculator::fetchDifficultItemChance($lootingChance, 100);

            if (! $canHave) {
                return;
            }

            $character->loadMissing('inventory.slots');

            $candidateItemIds = $items->pluck('id')->all();
            $ownedItemIds = $character->inventory->slots->pluck('item_id')->all();
            $ownedItemIdSet = array_fill_keys($ownedItemIds, true);

            $quests = Quest::query()
                ->where(function ($query) use ($candidateItemIds) {
                    $query->whereIn('item_id', $candidateItemIds)
                        ->orWhereIn('secondary_required_item', $candidateItemIds);
                })
                ->get();

            $questByItemId = [];

            foreach ($quests as $quest) {
                if (! is_null($quest->item_id) && in_array($quest->item_id, $candidateItemIds, true) && ! array_key_exists($quest->item_id, $questByItemId)) {
                    $questByItemId[$quest->item_id] = $quest->id;
                }

                if (! is_null($quest->secondary_required_item) && in_array($quest->secondary_required_item, $candidateItemIds, true) && ! array_key_exists($quest->secondary_required_item, $questByItemId)) {
                    $questByItemId[$quest->secondary_required_item] = $quest->id;
                }
            }

            $completedQuestIds = $character->questsCompleted()->pluck('quest_id')->all();
            $completedQuestIdSet = array_fill_keys($completedQuestIds, true);

            $eligibleItems = $items->filter(function (Item $item) use ($ownedItemIdSet, $questByItemId, $completedQuestIdSet): bool {
                $doesntHave = ! array_key_exists($item->id, $ownedItemIdSet);

                $questId = $questByItemId[$item->id] ?? null;

                if (! is_null($questId)) {
                    $isCompleted = array_key_exists($questId, $completedQuestIdSet);

                    return ! $isCompleted && $doesntHave;
                }

                return $doesntHave;
            });

            if ($eligibleItems->isNotEmpty()) {
                $this->attemptToPickUpItem($character, $eligibleItems->random());
            }
        }
    }

    /**
     * @param Character $character
     * @param string $gameMapName
     * @param Location|null $locationWithEffect
     * @return Item|null
     */
    protected function getDropFromCache(Character $character, string $gameMapName, ?Location $locationWithEffect = null): ?Item
    {
        return $this->randomItemDropBuilder->generateItem($this->getMaxLevelBasedOnPlane($character));
    }

    /**
     * @param Character $character
     * @return int
     */
    protected function getMaxLevelBasedOnPlane(Character $character): int
    {
        $characterLevel = $character->level;
        $mapType = $character->map->gameMap->mapType();

        if ($mapType->isSurface()) {
            if ($characterLevel >= 50) {
                return 50;
            }

            return $characterLevel;
        }

        if ($mapType->isLabyrinth()) {
            if ($characterLevel >= 150) {
                return 150;
            }

            return $characterLevel;
        }

        if ($mapType->isDungeons()) {
            if ($characterLevel >= 240) {
                return 240;
            }

            return $characterLevel;
        }

        if ($mapType->isHell()) {
            if ($characterLevel >= 300) {
                return 300;
            }

            return $characterLevel;
        }

        return 300;
    }

    /**
     * Attempts to pick up the item and give it to the player.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     * @throws Exception
     */
    protected function attemptToPickUpItem(Character $character, Item $item): void
    {
        $user = $character->user;

        if ($user->auto_disenchant && $item->type !== 'quest') {
            $this->autoDisenchantItem($character, $item);
        } else {
            if (! $character->isInventoryFull()) {
                $this->giveItemToPlayer($character, $item);
            } else {
                ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::INVENTORY_IS_FULL);
            }
        }
    }

    /**
     * Auto disenchants the item using the characters disenchanting skill.
     *
     * @param Character $character
     * @param Item $item
     * @throws Exception
     */
    private function autoDisenchantItem(Character $character, Item $item): void
    {
        $user = $character->user;

        if ($user->auto_disenchant_amount === 'all') {

            $this->handleDisenchantOrAutoSell($character, $item);

            return;
        }

        if ($user->auto_disenchant_amount === '1-billion') {
            $cost = SellItemCalculator::fetchSalePriceWithAffixes($item);

            if ($cost >= 1_000_000_000) {
                $this->giveItemToPlayer($character, $item);
            } else {
                $this->handleDisenchantOrAutoSell($character, $item);
            }
        }
    }

    /**
     * Handle either auto selling the item or auto disenchanting the item.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     * @throws Exception
     */
    private function handleDisenchantOrAutoSell(Character $character, Item $item): void {
        $maxCurrenciesValue = new MaxCurrenciesValue($character->gold_dust, MaxCurrenciesValue::GOLD_DUST);

        if ($character->user->auto_sell_item) {
            if ($maxCurrenciesValue->canNotGiveCurrency()) {
                $this->shopService->autoSellItem($character, $item);

                return;
            }
        }

        $this->disenchantService->setUp($character)->disenchantItemWithSkill();
    }

    /**
     * If the player can have the item, give it to them.
     *
     * @param Character $character
     * @param Item $item
     * @param bool $isMythic
     * @return void
     */
    private function giveItemToPlayer(Character $character, Item $item, bool $isMythic = false)
    {
        if ($this->canHaveItem($character, $item)) {
            $slot = $character->inventory->slots()->create([
                'item_id' => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            if ($item->type === 'quest') {
                $message = $character->name . ' has found: ' . $item->affix_name;

                event(new ServerMessageEvent($character->user, 'You found: ' . $item->affix_name . ' on the enemies corpse.', $slot->id));

                broadcast(new GlobalMessageEvent($message));
            } else {
                event(new ServerMessageEvent($character->user, 'You found: ' . $item->affix_name . ' on the enemies corpse.', $slot->id));

                if ($isMythic) {
                    event(new GlobalMessageEvent($character->name . ' Has found a mythical item on the enemies corpse! Such a rare drop!'));
                }
            }
        }
    }
}
