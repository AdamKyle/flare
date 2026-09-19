<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Currency\Values\CurrencyType;
use App\Game\Core\Items\Builders\RandomItemDropBuilder;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Maps\Values\LocationType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantService;
use Facades\App\Game\Core\Chance\DropCheckCalculator;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class BattleDrop
{
    use CanHaveQuestItem;

    private Monster $monster;

    private ?Location $locationWithEffect;

    private ?Location $manualQuestItemLocation = null;

    private float $gameMapBonus;

    private float $questItemDropBonus = 0.0;

    private float $lootingChance;

    private array $rewardTotals = [
        'auto_sold_gold' => 0,
    ];

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
     * Set the Monster whose drops are being resolved.
     *
     * @param Monster $monster
     * @return BattleDrop
     */
    public function setMonster(Monster $monster): BattleDrop
    {
        $this->monster = $monster;

        return $this;
    }

    /**
     * Set the special Location whose drop effect applies to this drop resolution.
     *
     * @param ?Location $location
     * @return BattleDrop
     */
    public function setSpecialLocation(?Location $location = null): BattleDrop
    {
        $this->locationWithEffect = $location;

        return $this;
    }

    /**
     * Set the Location that manually gates a quest item drop for this drop resolution.
     *
     * @param ?Location $location
     * @return BattleDrop
     */
    public function setManualQuestItemLocation(?Location $location = null): BattleDrop
    {
        $this->manualQuestItemLocation = $location;

        return $this;
    }

    /**
     * Set the Game Map's drop chance bonus.
     *
     * @param float $gameMapBonus
     * @return BattleDrop
     */
    public function setGameMapBonus(float $gameMapBonus = 0.0): BattleDrop
    {
        $this->gameMapBonus = $gameMapBonus;

        return $this;
    }

    /**
     * Set the resolved Gem quest item drop chance bonus for the Monster's own quest item drop check.
     *
     * @param float $questItemDropBonus
     * @return BattleDrop
     */
    public function setQuestItemDropBonus(float $questItemDropBonus = 0.0): BattleDrop
    {
        $this->questItemDropBonus = $questItemDropBonus;

        return $this;
    }

    /**
     * Set the Character's looting chance for this drop resolution.
     *
     * @param float $lootingChance
     * @return BattleDrop
     */
    public function setLootingChance(float $lootingChance = 0.0): BattleDrop
    {
        $this->lootingChance = $lootingChance;

        return $this;
    }

    /**
     * Reset the accumulated reward totals for a new drop resolution.
     *
     * @return BattleDrop
     */
    public function resetRewardTotals(): BattleDrop
    {
        $this->rewardTotals = [
            'auto_sold_gold' => 0,
        ];

        return $this;
    }

    /**
     * Return the reward totals accumulated during this drop resolution.
     *
     * @return array
     */
    public function rewardTotals(): array
    {
        return $this->rewardTotals;
    }

    /**
     * Handle fetching the drop for the player, attempting pickup unless the resolved Item is returned instead.
     *
     * @param Character $character
     * @param bool $canGetDrop
     * @param bool $returnItem
     * @return ?Item
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
     * Give the player a mythical item.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     */
    public function giveMythicItem(Character $character, Item $item): void
    {
        $this->giveItemToPlayer($character, $item, true);
    }

    /**
     * Handle the Monster's quest drop, attempting pickup unless the resolved Item is returned instead.
     *
     * @param Character $character
     * @param bool $returnItem
     * @return ?Item
     */
    public function handleMonsterQuestDrop(Character $character, bool $returnItem = false): ?Item
    {
        if (is_null($this->monster->quest_item_id)) {
            return null;
        }

        $canGetQuestItem = DropCheckCalculator::fetchQuestItemDropCheck($this->monster, $this->lootingChance, $this->gameMapBonus + $this->questItemDropBonus);

        if (! $canGetQuestItem) {
            return null;
        }

        if ($returnItem) {
            return $this->monster->questItem;
        }

        $this->attemptToPickUpItem($character, $this->monster->questItem);

        return null;
    }

    /**
     * Plan the Delve Location's quest item drop without applying it.
     *
     * @param Character $character
     * @return ?Item
     */
    public function planDelveLocationQuestItem(Character $character): ?Item
    {
        $automation = $character->currentAutomations()->where('type', AutomationType::DELVE->value)->first();

        if (is_null($automation)) {
            return null;
        }

        $location = Location::where('type', LocationType::CAVE_OF_SHADOWS->value)
            ->where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->whereNotNull('hours_to_drop')
            ->first();

        if (is_null($location)) {
            return null;
        }

        if (now()->diffInHours($automation->started_at) < $location->hours_to_drop) {
            return null;
        }

        return $this->eligibleLocationQuestItem($character, $location, $this->lootingChance);
    }

    /**
     * Plan the special Location's manually gated quest item drop without applying it.
     *
     * @param Character $character
     * @return ?Item
     */
    public function planSpecialLocationQuestItem(Character $character): ?Item
    {
        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING->value)->exists()) {
            return null;
        }

        if (is_null($this->manualQuestItemLocation)) {
            return null;
        }

        return $this->eligibleLocationQuestItem($character, $this->manualQuestItemLocation, min($this->lootingChance, 0.45));
    }

    /**
     * Handle the Character's Delve Location quest item drop when in a Delve automation.
     *
     * @param Character $character
     * @return void
     */
    public function handleDelveLocationQuestItems(Character $character): void
    {
        $automation = $character->currentAutomations()->where('type', AutomationType::DELVE->value)->first();

        if (is_null($automation)) {
            return;
        }

        $location = Location::where('type', LocationType::CAVE_OF_SHADOWS->value)
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
            ->where('type', 'quest')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        if (! DropCheckCalculator::fetchDifficultItemChance($this->lootingChance, 100)) {
            return;
        }

        $character->loadMissing('inventory.slots');

        $ownedItemIds = $character->inventory->slots->pluck('item_id')->all();

        $completedQuestIds = $character->questsCompleted()
            ->whereNotNull('quest_id')
            ->pluck('quest_id')
            ->all();

        $blockedItemIds = [];

        if (! empty($completedQuestIds)) {
            $blockedItemIds = Quest::query()
                ->whereIn('id', $completedQuestIds)
                ->get(['item_id', 'secondary_required_item'])
                ->flatMap(function (Quest $quest): array {
                    return [
                        $quest->item_id,
                        $quest->secondary_required_item,
                    ];
                })
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $eligibleItems = $items->filter(function (Item $item) use ($ownedItemIds, $blockedItemIds): bool {
            return ! in_array($item->id, $ownedItemIds, true)
                && ! in_array($item->id, $blockedItemIds, true);
        });

        if ($eligibleItems->isEmpty()) {
            return;
        }

        $this->attemptToPickUpItem($character, $eligibleItems->random());
    }

    /**
     * Handle the special Location's manually gated quest item drop.
     *
     * @param Character $character
     * @return void
     */
    public function handleSpecialLocationQuestItem(Character $character): void
    {
        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING->value)->exists()) {
            return;
        }

        if (is_null($this->manualQuestItemLocation)) {
            return;
        }

        $lootingChance = min($this->lootingChance, 0.45);

        $items = Item::where('drop_location_id', $this->manualQuestItemLocation->id)
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->where('type', 'quest')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        if (! DropCheckCalculator::fetchDifficultItemChance($lootingChance, 100)) {
            return;
        }

        $character->loadMissing('inventory.slots');

        $ownedItemIds = $character->inventory->slots->pluck('item_id')->all();

        $completedQuestIds = $character->questsCompleted()
            ->whereNotNull('quest_id')
            ->pluck('quest_id')
            ->all();

        $blockedItemIds = [];

        if (! empty($completedQuestIds)) {
            $blockedItemIds = Quest::query()
                ->whereIn('id', $completedQuestIds)
                ->get(['item_id', 'secondary_required_item'])
                ->flatMap(function (Quest $quest): array {
                    return [
                        $quest->item_id,
                        $quest->secondary_required_item,
                    ];
                })
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $eligibleItems = $items->filter(function (Item $item) use ($ownedItemIds, $blockedItemIds): bool {
            return ! in_array($item->id, $ownedItemIds, true)
                && ! in_array($item->id, $blockedItemIds, true);
        });

        if ($eligibleItems->isEmpty()) {
            return;
        }

        $this->attemptToPickUpItem($character, $eligibleItems->random());
    }

    /**
     * Apply one already-resolved planned Item reward to the Character.
     *
     * @param Character $character
     * @param Item $item
     * @param bool $isMythic
     * @return void
     */
    public function applyPlannedItem(Character $character, Item $item, bool $isMythic = false): void
    {
        if ($isMythic) {
            $this->giveMythicItem($character, $item);

            return;
        }

        $this->attemptToPickUpItem($character, $item);
    }

    /**
     * Resolve one eligible, not-yet-owned, not-yet-blocked quest Item for the given Location.
     *
     * @param Character $character
     * @param Location $location
     * @param float $lootingChance
     * @return ?Item
     */
    private function eligibleLocationQuestItem(Character $character, Location $location, float $lootingChance): ?Item
    {
        $items = Item::where('drop_location_id', $location->id)
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->where('type', 'quest')
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        if (! DropCheckCalculator::fetchDifficultItemChance($lootingChance, 100)) {
            return null;
        }

        $character->loadMissing('inventory.slots');

        $ownedItemIds = $character->inventory->slots->pluck('item_id')->all();

        $completedQuestIds = $character->questsCompleted()
            ->whereNotNull('quest_id')
            ->pluck('quest_id')
            ->all();

        $blockedItemIds = [];

        if (! empty($completedQuestIds)) {
            $blockedItemIds = Quest::query()
                ->whereIn('id', $completedQuestIds)
                ->get(['item_id', 'secondary_required_item'])
                ->flatMap(function (Quest $quest): array {
                    return [
                        $quest->item_id,
                        $quest->secondary_required_item,
                    ];
                })
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $eligibleItems = $items->filter(function (Item $item) use ($ownedItemIds, $blockedItemIds): bool {
            return ! in_array($item->id, $ownedItemIds, true)
                && ! in_array($item->id, $blockedItemIds, true);
        });

        if ($eligibleItems->isEmpty()) {
            return null;
        }

        return $eligibleItems->random();
    }

    /**
     * Generate a random Item drop scaled to the Character's plane-based max level.
     *
     * @param Character $character
     * @param string $gameMapName
     * @param ?Location $locationWithEffect
     * @return ?Item
     */
    private function getDropFromCache(Character $character, string $gameMapName, ?Location $locationWithEffect = null): ?Item
    {
        return $this->randomItemDropBuilder->generateItem($this->getMaxLevelBasedOnPlane($character));
    }

    /**
     * Resolve the Character's max Item level based on their current plane's map type.
     *
     * @param Character $character
     * @return int
     */
    private function getMaxLevelBasedOnPlane(Character $character): int
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
     * Attempt to pick up the Item, routing it to auto-disenchant when the player has that setting enabled.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     */
    private function attemptToPickUpItem(Character $character, Item $item): void
    {
        $user = $character->user;

        if ($user->auto_disenchant && $item->type !== 'quest') {
            $this->autoDisenchantItem($character, $item);

            return;
        }

        if ($character->isInventoryFull()) {
            ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::INVENTORY_IS_FULL);

            return;
        }

        $this->giveItemToPlayer($character, $item);
    }

    /**
     * Auto disenchant the Item using the Character's Disenchanting skill, per the player's auto-disenchant amount setting.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     */
    private function autoDisenchantItem(Character $character, Item $item): void
    {
        $user = $character->user;

        if ($user->auto_disenchant_amount === 'all') {
            $this->handleDisenchantOrAutoSell($character, $item);

            return;
        }

        if ($user->auto_disenchant_amount !== '1-billion') {
            return;
        }

        $cost = SellItemCalculator::fetchSalePriceWithAffixes($item);

        if ($cost >= 1_000_000_000) {
            $this->giveItemToPlayer($character, $item);

            return;
        }

        $this->handleDisenchantOrAutoSell($character, $item);
    }

    /**
     * Handle either auto selling the Item or auto disenchanting the Item, depending on the player's settings and Gold Dust cap.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     */
    private function handleDisenchantOrAutoSell(Character $character, Item $item): void
    {
        $maxCurrenciesValue = new CurrencyLimit($character->gold_dust, CurrencyType::GOLD_DUST);

        if ($character->user->auto_sell_item && $maxCurrenciesValue->canNotGiveCurrency()) {
            $this->rewardTotals['auto_sold_gold'] += SellItemCalculator::fetchSalePriceWithAffixes($item);

            $this->shopService->autoSellItem($character, $item);

            return;
        }

        $this->disenchantService->setUp($character)->disenchantItemWithSkill();
    }

    /**
     * If the player can have the Item, give it to them.
     *
     * @param Character $character
     * @param Item $item
     * @param bool $isMythic
     * @return void
     */
    private function giveItemToPlayer(Character $character, Item $item, bool $isMythic = false): void
    {
        if ($item->type === 'quest') {
            $this->giveQuestItemToPlayer($character, $item);

            return;
        }

        if (! $this->canHaveItem($character, $item)) {
            return;
        }

        $slot = $character->inventory->slots()->create([
            'item_id' => $item->id,
            'inventory_id' => $character->inventory->id,
        ]);

        ServerMessageHandler::sendBasicMessageWithId($character->user, 'You found: '.$item->affix_name.' on the enemies corpse.', $slot->id);

        if ($isMythic) {
            event(new GlobalMessageEvent($character->name.' Has found a mythical item on the enemies corpse! Such a rare drop!'));
        }
    }

    /**
     * Give a quest Item to the player, locking the character's inventory row for the duration of the ownership check and slot creation so concurrent drop processes cannot both insert the same quest item.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     */
    private function giveQuestItemToPlayer(Character $character, Item $item): void
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        if (! $this->canHaveItem($character, $item)) {
            return;
        }

        $slot = $inventory->slots()->create([
            'item_id' => $item->id,
            'inventory_id' => $inventory->id,
        ]);

        $message = $character->name.' has found: '.$item->affix_name;

        ServerMessageHandler::sendBasicMessageWithId($character->user, 'You found: '.$item->affix_name.' on the enemies corpse.', $slot->id);

        broadcast(new GlobalMessageEvent($message));
    }
}
