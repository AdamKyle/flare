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
use Exception;
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

    public function setManualQuestItemLocation(?Location $location = null): BattleDrop
    {
        $this->manualQuestItemLocation = $location;

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
     * Set the resolved Gem quest item drop chance bonus for the Monster's own quest item drop check.
     */
    public function setQuestItemDropBonus(float $questItemDropBonus = 0.0): BattleDrop
    {
        $this->questItemDropBonus = $questItemDropBonus;

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

    public function resetRewardTotals(): BattleDrop
    {
        $this->rewardTotals = [
            'auto_sold_gold' => 0,
        ];

        return $this;
    }

    public function rewardTotals(): array
    {
        return $this->rewardTotals;
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
     * Handle when a character is in a delve exploration for quest items
     *
     * @throws Exception
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
     * @throws Exception
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

    public function applyPlannedItem(Character $character, int $itemId, bool $isMythic = false): void
    {
        $item = Item::find($itemId);

        if (is_null($item)) {
            return;
        }

        if ($isMythic) {
            $this->giveMythicItem($character, $item);

            return;
        }

        $this->attemptToPickUpItem($character, $item);
    }

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

    protected function getDropFromCache(Character $character, string $gameMapName, ?Location $locationWithEffect = null): ?Item
    {
        return $this->randomItemDropBuilder->generateItem($this->getMaxLevelBasedOnPlane($character));
    }

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
     * @throws Exception
     */
    private function handleDisenchantOrAutoSell(Character $character, Item $item): void
    {
        $maxCurrenciesValue = new CurrencyLimit($character->gold_dust, CurrencyType::GOLD_DUST);

        if ($character->user->auto_sell_item) {
            if ($maxCurrenciesValue->canNotGiveCurrency()) {
                $this->rewardTotals['auto_sold_gold'] += SellItemCalculator::fetchSalePriceWithAffixes($item);

                $this->shopService->autoSellItem($character, $item);

                return;
            }
        }

        $this->disenchantService->setUp($character)->disenchantItemWithSkill();
    }

    /**
     * If the player can have the item, give it to them.
     *
     * @return void
     */
    private function giveItemToPlayer(Character $character, Item $item, bool $isMythic = false)
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
     * Give a quest item to the player.
     *
     * Locks the character's inventory row for the duration of the ownership
     * check and slot creation so concurrent drop processes cannot both
     * insert the same quest item.
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
