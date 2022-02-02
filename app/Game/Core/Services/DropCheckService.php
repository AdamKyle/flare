<?php

namespace App\Game\Core\Services;


use App\Flare\Models\User;
use App\Flare\Values\MapNameValue;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Values\LocationEffectValue;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Skills\Services\DisenchantService;
use Illuminate\Support\Facades\Cache;

class DropCheckService {

    use CanHaveQuestItem;

    const ROLL = 1000000000;

    const GENERATE_RANDOM_ITEM = 999999999;

    private $randomItemDropBuilder;

    private $disenchantService;

    private $monster;

    private $adventure;

    private $locationWithEffect;

    private $lootingChance = 0.0;

    private $gameMapBonus = 0.0;

    public function __construct(RandomItemDropBuilder $randomItemDropBuilder, DisenchantService $disenchantService) {
        $this->randomItemDropBuilder = $randomItemDropBuilder;
        $this->disenchantService     = $disenchantService;
    }

    public function process(Character $character, Monster $monster, Adventure $adventure = null) {
        $this->lootingChance  = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $this->monster        = $monster;
        $this->adventure      = $adventure;

        $gameMap              = $character->map->gameMap;
        $characterMap         = $character->map;

        if (!is_null($gameMap->drop_chance_bonus)) {
            $this->gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $this->findLocationWithEffect($characterMap);

        $this->handleDropChance($character);
    }

    public function handleDropChance(Character $character) {
        $canGetDrop = $this->canHaveDrop();

        $this->handleDrop($character, $canGetDrop);

        $this->handleMonsterQuestDrop($character);

        if (!is_null($this->locationWithEffect)) {
            $this->handleSpecialLocationQuestItem($character);
        }
    }

    public function findLocationWithEffect(Map $map) {
        $this->locationWithEffect = Location::whereNotNull('enemy_strength_type')
                                            ->where('x', $map->character_position_x)
                                            ->where('y', $map->character_position_y)
                                            ->where('game_map_id', $map->game_map_id)
                                            ->first();
    }

    protected function canHaveDrop() {
        if (!is_null($this->locationWithEffect)) {
            $dropRate   = new LocationEffectValue($this->locationWithEffect->enemy_strength_type);

            return DropCheckCalculator::fetchLocationDropChance($dropRate->fetchDropRate());
        }

        return DropCheckCalculator::fetchDropCheckChance($this->monster, $this->lootingChance, $this->gameMapBonus, $this->adventure);
    }

    protected function handleDrop(Character $character, bool $canGetDrop) {
        if ($canGetDrop) {

            $drop = $this->getDropFromCache($character, $this->monster->gameMap->name, $this->locationWithEffect);

            if (is_null($drop)) {
                $drop = $this->randomItemDropBuilder
                    ->setLocation($this->locationWithEffect)
                    ->setMonsterPlane($this->monster->gameMap->name)
                    ->setCharacterLevel($character->level)
                    ->setMonsterMaxLevel($this->monster->max_level)
                    ->generateItem();
            }

            if (!is_null($drop)) {
                if (!is_null($drop->itemSuffix) || !is_null($drop->itemPrefix)) {
                    $this->attemptToPickUpItem($character, $drop);

                    event(new CharacterInventoryUpdateBroadCastEvent($character->user));
                }
            }
        }
    }

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

        return $this->getDrop('droppable-items');
    }

    protected function getDrop(string $cacheName): ?Item {
        if (Cache::has($cacheName)) {
            $items = Cache::get($cacheName);

            if (count($items) > 100) {
                return Item::find($items[rand(0, (count($items) - 1))]);
            } else {
                $roll = rand(0, self::ROLL);

                if ($roll < self::GENERATE_RANDOM_ITEM) {
                    return Item::find($items[rand(0, (count($items) - 1))]);
                }
            }
        }

        return null;
    }

    protected function handleMonsterQuestDrop(Character $character) {
        if (!is_null($this->monster->quest_item_id)) {
            $canGetQuestItem = DropCheckCalculator::fetchQuestItemDropCheck($this->monster, $this->lootingChance, $this->gameMapBonus, $this->adventure);
            dump($canGetQuestItem);
            if ($canGetQuestItem) {
                $this->attemptToPickUpItem($character, $this->monster->questItem);

                event(new CharacterInventoryUpdateBroadCastEvent($character->user));
            }
        }
    }

    protected function handleSpecialLocationQuestItem(Character $character) {
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

                    return;
                }
            }
        }
    }

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