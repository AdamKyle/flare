<?php

namespace App\Game\Battle\Services;

use Illuminate\Support\Facades\Cache;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\AutomationType;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Skills\Services\DisenchantService;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Game\Messages\Events\ServerMessageEvent as GameServerMessage;

class BattleDrop {

    use CanHaveQuestItem;

    private $randomItemDropBuilder;

    private $disenchantService;

    private $monster;

    private $locationWithEffect;

    private $gameMapBonus;

    private $lootingChance;

    public function __construct(RandomItemDropBuilder $randomItemDropBuilder, DisenchantService $disenchantService) {
        $this->randomItemDropBuilder = $randomItemDropBuilder;
        $this->disenchantService     = $disenchantService;
    }

    public function setMonster(Monster $monster): BattleDrop {
        $this->monster = $monster;

        return $this;
    }

    public function setSpecialLocation(Location $location = null): BattleDrop {
        $this->locationWithEffect = $location;

        return $this;
    }

    public function setGameMapBonus(float $gameMapBonus = 0.0): BattleDrop {
        $this->gameMapBonus = $gameMapBonus;

        return $this;
    }

    public function setLootingChance(float $lootingChance = 0.0): BattleDrop {
        $this->lootingChance = $lootingChance;

        return $this;
    }

    /**
     * Handles fetching the drop for the player.
     *
     * If the player can get the drop we will handle all aspects including
     * attempting to pick up the drop.
     *
     * @param Character $character
     * @param bool $canGetDrop
     * @param bool $returnItem
     * @return Item|null
     */
    public function handleDrop(Character $character, bool $canGetDrop, bool $returnItem = false): ?Item {
        if ($canGetDrop) {
            $drop = $this->getDropFromCache($character, $this->monster->gameMap->name, $this->locationWithEffect);

            if (!is_null($drop)) {
                if ((!is_null($drop->itemSuffix) || !is_null($drop->itemPrefix))  && !$returnItem) {
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
     * @param Character $character
     * @param Item $item
     * @return void
     */
    public function giveMythicItem(Character $character, Item $item) {
        $this->giveItemToPlayer($character, $item, true);
    }

    /**
     * Handles the monsters quest drop.
     *
     * Can return the item.
     *
     * @param Character $character
     * @return void
     */
    public function handleMonsterQuestDrop(Character $character, bool $returnItem = false): ?Item {
        if (!is_null($this->monster->quest_item_id)) {
            $canGetQuestItem = DropCheckCalculator::fetchQuestItemDropCheck($this->monster, $character->level, $this->lootingChance, $this->gameMapBonus);

            if ($canGetQuestItem && !$returnItem) {
                $this->attemptToPickUpItem($character, $this->monster->questItem);

            } else if ($canGetQuestItem && $returnItem) {
                return $this->monster->questItem;
            }
        }

        return null;
    }

    /**
     * Handles drops for special locations.
     *
     * @param Character $character
     * @return void
     */
    public function handleSpecialLocationQuestItem(Character $character) {
        $automation = $character->currentAutomations()->where('type', AutomationType::EXPLORING)->first();

        if (!is_null($automation)) {
            return;
        }

        $lootingChance = $this->lootingChance > 0.45 ? 0.45 : $this->lootingChance;

        $items = Item::where('drop_location_id', $this->locationWithEffect->id)->where('type', 'quest')->get();

        if ($items->isNotEmpty()) {

            foreach ($items as $item) {
                if ($this->canHaveItem($character, $item)) {
                    $chance = 95;
                    $roll = RandomNumberGenerator::generateRandomNumber(1, 50, 1, 100);;
                    $roll = $roll + $roll * $lootingChance;

                    if ($roll > $chance) {
                        $this->attemptToPickUpItem($character, $item);

                        return;
                    }

                    return;
                }
            }
        }
    }

    /**
     * Depending on the map name and the location, we fetch the drop item from the cache.
     *
     * @param Character $character
     * @param string $gameMapName
     * @param Location|null $locationWithEffect
     * @return Item|null
     */
    protected function getDropFromCache(Character $character, string $gameMapName, Location $locationWithEffect = null): ?Item {
        return $this->randomItemDropBuilder->generateItem($this->getMaxLevelBasedOnPlane($character));
    }

    /**
     * @param Character $character
     * @return int
     */
    protected function getMaxLevelBasedOnPlane(Character $character): int {
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

    protected function getCacheDrop(Character $character, string $gameMapName, Location $locationWithEffect = null): ?Item {
        $levelDifference = $character->level - $this->monster->max_level;

        if ($gameMapName === MapNameValue::SHADOW_PLANE) {
            if ($levelDifference >= 10) {
                return $this->getDrop('highend-droppable-items');
            }
        }

        if (!is_null($locationWithEffect)) {
            if ($levelDifference >= 10) {
                return $this->getDrop('highend-droppable-items');
            }
        }

        return null;
    }

    /**
     * Gets drop from the cache. Can return null.
     *
     * @param string $cacheName
     * @return Item|null
     */
    protected function getDrop(string $cacheName): ?Item {
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
     * @param Character $character
     * @param Item $item
     * @return void
     */
    protected function attemptToPickUpItem(Character $character, Item $item) {
        $user      = $character->user;

        if ($user->auto_disenchant && $item->type !== 'quest') {
            $this->autoDisenchantItem($character, $item);
        } else {
            if (!$character->isInventoryFull()) {

                $this->giveItemToPlayer($character, $item);
            } else {
                event(new ServerMessageEvent($character->user, 'inventory_full'));
            }
        }
    }

    /**
     * Auto disenchants the item using the characters disenchanting skill.
     *
     * @param Character $character
     * @param $item
     * @return void
     */
    private function autoDisenchantItem(Character $character, $item) {
        $user = $character->user;

        if ($user->auto_disenchant_amount === 'all') {
            $this->disenchantService->disenchantItemWithSkill($character->refresh(), false);
        }

        if ($user->auto_disenchant_amount === '1-billion') {
            $cost = SellItemCalculator::fetchSalePriceWithAffixes($item);

            if ($cost >= 1000000000) {
                $slot = $character->refresh()->inventory->slots()->where('item_id', $item->id)->first();

                event(new ServerMessageEvent($character->user, 'You found: ' . $item->affix_name . ' on the enemies corpse.', $slot->id));
            } else {
                $this->disenchantService->disenchantItemWithSkill($character->refresh(), false);
            }
        }
    }

    /**
     * If the player can have the item, give it to them.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     */
    private function giveItemToPlayer(Character $character, Item $item, bool $isMythic = false) {
        if ($this->canHaveItem($character, $item)) {
            $character->inventory->slots()->create([
                'item_id' => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            if ($item->type === 'quest') {
                $message = $character->name . ' has found: ' . $item->affix_name;

                $slot = $character->refresh()->inventory->slots()->where('item_id', $item->id)->first();

                event(new GameServerMessage($character->user, 'You found: ' . $item->affix_name . ' on the enemies corpse.', $item->id, true));

                broadcast(new GlobalMessageEvent($message));
            } else {
                $slot = $character->refresh()->inventory->slots()->where('item_id', $item->id)->first();

                event(new GameServerMessage($character->user, 'You found: ' . $item->affix_name . ' on the enemies corpse.', $slot->id));

                if ($isMythic) {
                    event(new GlobalMessageEvent($character->name . ' Has found a mythical item on the king!'));
                }
            }
        }
    }
}
