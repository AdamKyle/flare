<?php

namespace App\Game\Battle\Services;

use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Values\MapNameValue;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Skills\Services\DisenchantService;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Support\Facades\Cache;

class BattleDrop {

    use CanHaveQuestItem;

    const ROLL = 1000000000;

    const GENERATE_RANDOM_ITEM = 999999999;

    private $randomeItemDropBuilder;

    private $disenchantService;

    private $monster;

    private $locationWithEffect;

    private $adventure;

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

    public function setAdventure(Adventure $adventure = null): BattleDrop {
        $this->adventure = $adventure;

        return $this;
    }

    public function setLootingChance(float $lootingChance = 0.0): BattleDrop {
        $this->lootingChance = $lootingChance;

        return $this;
    }

    /**
     * Handles fetching the drop for the player.
     *
     * If the player can get the drop we will handle all spects including
     * attempting to pick up the drop.
     *
     * @param Character $character
     * @param bool $canGetDrop
     * @return void
     */
    public function handleDrop(Character $character, bool $canGetDrop, bool $returnItem = false): ?Item {
        if ($canGetDrop) {

            $drop = $this->getDropFromCache($character, $this->monster->gameMap->name, $this->locationWithEffect);

            if (!is_null($drop)) {
                if ((!is_null($drop->itemSuffix) || !is_null($drop->itemPrefix))  && !$returnItem) {
                    $this->attemptToPickUpItem($character, $drop);

                    event(new CharacterInventoryUpdateBroadCastEvent($character->user));

                    event(new CharacterInventoryDetailsUpdate($character->user));
                } else {
                    return $drop;
                }
            }
        }

        return null;
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
            $canGetQuestItem = DropCheckCalculator::fetchQuestItemDropCheck($this->monster, $this->lootingChance, $this->gameMapBonus, $this->adventure);

            if ($canGetQuestItem && !$returnItem) {
                $this->attemptToPickUpItem($character, $this->monster->questItem);

                event(new CharacterInventoryUpdateBroadCastEvent($character->user));

                event(new CharacterInventoryDetailsUpdate($character->user));
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
        $automation = $character->currentAutomations()->where('type', AutomationType::ATTACK)->first();

        if (!is_null($automation)) {
            return; // Characters cannot use automation to get these.
        }

        $characterLevel  = $character->level;
        $monsterMaxLevel = $this->monster->max_level;
        $levelDifference = $monsterMaxLevel - $characterLevel;

        if (!($levelDifference >= 10)) {
            return; // The monster must be 10 levels or higher than the character for this to drop.
        }

        $lootingChance = $this->lootingChance > 0.45 ? 0.45 : $this->lootingChance;

        $items = Item::where('drop_location_id', $this->locationWithEffect->id)->where('type', 'quest')->get();

        if ($items->isNotEmpty()) {

            foreach ($items as $item) {
                $chance = 999999;
                $roll   = rand(1, 1000000);

                $roll = $roll + $roll * $lootingChance;

                if ($roll > $chance) {
                    $this->attemptToPickUpItem($character, $item);

                    event(new CharacterInventoryUpdateBroadCastEvent($character->user));

                    event(new CharacterInventoryDetailsUpdate($character->user));

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

        $drop = $this->getDrop('droppable-items');

        if (is_null($drop)) {
            $drop = $this->randomItemDropBuilder
                         ->setLocation($this->locationWithEffect)
                         ->setMonsterPlane($this->monster->gameMap->name)
                         ->setCharacterLevel($character->level)
                         ->setMonsterMaxLevel($this->monster->max_level)
                         ->generateItem();
        }

        return $drop;
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
                event(new ServerMessageEvent($character->user, 'gained_item', $item->affix_name, route('game.items.item', [
                    'item' => $item
                ]), $item->id));
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
    private function giveItemToPlayer(Character $character, Item $item) {
        if ($this->canHaveItem($character, $item)) {
            $character->inventory->slots()->create([
                'item_id' => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            if ($item->type === 'quest') {
                $message = $character->name . ' has found: ' . $item->affix_name;

                event(new ServerMessageEvent($character->user, 'gained_item', $item->affix_name, route('game.items.item', [
                    'item' => $item
                ]), $item->id));

                broadcast(new GlobalMessageEvent($message));
            } else {
                event(new ServerMessageEvent($character->user, 'gained_item', $item->affix_name, route('game.items.item', [
                    'item' => $item
                ]), $item->id));
            }
        }
    }
}
