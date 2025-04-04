<?php

namespace App\Game\Battle\Services;

use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Flare\Models\User;
use App\Flare\Values\AutomationType;
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
use Illuminate\Support\Facades\Cache;

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
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function handleSpecialLocationQuestItem(Character $character)
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

            $items = collect($items)->filter(function ($item) use ($character) {
                $doesntHave = $character->inventory->slots->where('item_id', '=', $item->id)->isEmpty();

                $questThatNeedsThisItem = Quest::where('item_id', $item->id)->orWhere('secondary_required_item', $item->id)->first();

                if (! is_null($questThatNeedsThisItem)) {
                    $completedQuest = $character->questsCompleted()->where('quest_id', $questThatNeedsThisItem->id)->first();

                    return is_null($completedQuest) && $doesntHave;
                }

                return $doesntHave;
            });

            $canHave = DropCheckCalculator::fetchDifficultItemChance($lootingChance, 100);

            if ($items->isNotEmpty() && $canHave) {
                $this->attemptToPickUpItem($character, $items->random());
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

        if ($character->map->gameMap->mapType()->isSurface()) {
            if ($characterLevel >= 50) {
                return 50;
            }

            return $characterLevel;
        }

        if ($character->map->gameMap->mapType()->isLabyrinth()) {
            if ($characterLevel >= 150) {
                return 150;
            }

            return $characterLevel;
        }

        if ($character->map->gameMap->mapType()->isDungeons()) {
            if ($characterLevel >= 240) {
                return 240;
            }

            return $characterLevel;
        }

        if ($character->map->gameMap->mapType()->isHell()) {
            if ($characterLevel >= 300) {
                return 300;
            }

            return $characterLevel;
        }

        return 300;
    }

    /**
     * Gets drop from the cache. Can return null.
     */
    protected function getDrop(string $cacheName): ?Item
    {
        if (Cache::has($cacheName)) {
            $items = Cache::get($cacheName);

            if (count($items) < 75) {
                return null;
            }

            return Item::find($items[rand(0, (count($items) - 1))]);
        }

        return null;
    }

    /**
     * Attempts to pick up the item and give it to the player.
     *
     * @return void
     * @throws Exception
     */
    protected function attemptToPickUpItem(Character $character, Item $item)
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
     * @return void
     */
    private function giveItemToPlayer(Character $character, Item $item, bool $isMythic = false)
    {
        if ($this->canHaveItem($character, $item)) {
            $character->inventory->slots()->create([
                'item_id' => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            if ($item->type === 'quest') {
                $message = $character->name . ' has found: ' . $item->affix_name;

                $slot = $character->refresh()->inventory->slots()->where('item_id', $item->id)->first();

                event(new ServerMessageEvent($character->user, 'You found: ' . $item->affix_name . ' on the enemies corpse.', $slot->id));

                broadcast(new GlobalMessageEvent($message));
            } else {
                $slot = $character->refresh()->inventory->slots()->where('item_id', $item->id)->first();

                event(new ServerMessageEvent($character->user, 'You found: ' . $item->affix_name . ' on the enemies corpse.', $slot->id));

                if ($isMythic) {
                    event(new GlobalMessageEvent($character->name . ' Has found a mythical item on the enemies corpse! Such a rare drop!'));
                }
            }
        }
    }
}
