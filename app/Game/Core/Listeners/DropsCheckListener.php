<?php

namespace App\Game\Core\Listeners;

use App\Flare\Models\Location;
use App\Flare\Values\LocationEffectValue;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\DropsCheckEvent;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Item;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Skills\Services\DisenchantService;
use Facades\App\Flare\Calculators\DropCheckCalculator;


class DropsCheckListener
{

    use CanHaveQuestItem;

    private $randomItemDropBuilder;

    private $disenchantService;

    public function __construct(RandomItemDropBuilder $randomItemDropBuilder, DisenchantService $disenchantService) {
        $this->randomItemDropBuilder = $randomItemDropBuilder;
        $this->disenchantService     = $disenchantService;
    }

    /**
     * Handle the event.
     *
     * @param DropsCheckEvent $event
     * @return void
     */
    public function handle(DropsCheckEvent $event)
    {
        $lootingChance  = $event->character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $gameMap        = $event->character->map->gameMap;
        $gameMapBonus   = 0.0;
        $characterMap   = $event->character->map;

        if (!is_null($gameMap->drop_chance_bonus)) {
            $gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $locationWithEffect   = Location::whereNotNull('enemy_strength_type')
            ->where('x', $characterMap->character_position_x)
            ->where('y', $characterMap->character_position_y)
            ->where('game_map_id', $characterMap->game_map_id)
            ->first();

        $canGetDrop = $this->canHaveDrop($event, $lootingChance, $gameMapBonus, $locationWithEffect);
        
        $this->handleDrop($event, $canGetDrop, $locationWithEffect);

        $this->handleMonsterQuestDrop($event, $lootingChance, $gameMapBonus);

        if (!is_null($locationWithEffect)) {
            $this->handleSpecialLocationQuestItem($event, $locationWithEffect, $lootingChance);
        }
    }

    protected function canHaveDrop(DropsCheckEvent $event, float $lootingChance, float $gameMapBonus, Location $locationWithEffect = null) {
        if (!is_null($locationWithEffect)) {
            $dropRate   = new LocationEffectValue($locationWithEffect->enemy_strength_type);

            return DropCheckCalculator::fetchLocationDropChance($dropRate->fetchDropRate());
        }

        return DropCheckCalculator::fetchDropCheckChance($event->monster, $lootingChance, $gameMapBonus, $event->adventure);
    }

    protected function handleDrop(DropsCheckEvent $event, bool $canGetDrop, Location $locationWithEffect = null) {
        if ($canGetDrop) {
            $drop = $this->randomItemDropBuilder
                ->setLocation($locationWithEffect)
                ->setMonsterPlane($event->monster->gameMap->name)
                ->setCharacterLevel($event->character->level)
                ->setMonsterMaxLevel($event->monster->max_level)
                ->generateItem();

            if (!is_null($drop)) {
                if (!is_null($drop->itemSuffix) || !is_null($drop->itemPrefix)) {
                    $this->attemptToPickUpItem($event, $drop);

                    event(new CharacterInventoryUpdateBroadCastEvent($event->character->user));
                }
            }
        }
    }

    protected function handleMonsterQuestDrop(DropsCheckEvent $event, float $lootingChance, float $gameMapBonus) {
        if (!is_null($event->monster->quest_item_id)) {
            $canGetQuestItem = DropCheckCalculator::fetchQuestItemDropCheck($event->monster, $lootingChance, $gameMapBonus, $event->adventure);

            if ($canGetQuestItem) {
                $this->attemptToPickUpItem($event, $event->monster->questItem);

                event(new CharacterInventoryUpdateBroadCastEvent($event->character->user));
            }
        }
    }

    protected function handleSpecialLocationQuestItem(DropsCheckEvent $event, Location $locationWithEffect, float $lootingChance) {
        $automation = $event->character->currentAutomations()->where('type', AutomationType::ATTACK)->first();

        if (!is_null($automation)) {
            return; // Characters cannot use automation to get these.
        }

        $characterLevel  = $event->character->level;
        $monsterMaxLevel = $event->monster->max_level;
        $levelDifference = $monsterMaxLevel - $characterLevel;

        if (!($levelDifference >= 10)) {
            return; // The monster must be 10 levels or higher than the character for this to drop.
        }

        $lootingChance = $lootingChance > 0.45 ? 0.45 : $lootingChance;

        $items = Item::where('drop_location_id', $locationWithEffect->id)->where('type', 'quest')->get();

        if ($items->isNotEmpty()) {
            foreach ($items as $item) {
                $chance = 999999;
                $roll   = rand(1, 1000000);

                $roll = $roll + $roll * $lootingChance;

                if ($roll > $chance) {
                    $this->attemptToPickUpItem($event, $item);

                    event(new CharacterInventoryUpdateBroadCastEvent($event->character->user));
                }
            }

        }
    }

    protected function attemptToPickUpItem(DropsCheckEvent $event, Item $item) {
        $character = $event->character;
        $user      = $character->user;

        if (!$character->isInventoryFull()) {

            if ($this->canHaveItem($character, $item)) {
                $slot = $character->inventory->slots()->create([
                    'item_id' => $item->id,
                    'inventory_id' => $character->inventory->id,
                ]);

                if ($item->type === 'quest') {
                    $message = $event->character->name . ' has found: ' . $item->affix_name;

                    event(new ServerMessageEvent($event->character->user, 'gained_item', $item->affix_name, route('game.items.item', [
                        'item' => $item
                    ]), $item->id));

                    broadcast(new GlobalMessageEvent($message));
                } else if ($user->auto_disenchant) {
                    if ($user->auto_disenchant_amount === 'all') {
                        $this->disenchantService->disenchantWithSkill($character->refresh(), $slot);
                    }

                    if ($user->auto_disenchant_amount === '1-billion') {
                        $cost = SellItemCalculator::fetchSalePriceWithAffixes($slot->item);

                        if ($cost >= 1000000000) {
                            event(new ServerMessageEvent($event->character->user, 'gained_item', $item->affix_name, route('game.items.item', [
                                'item' => $item
                            ]), $item->id));
                        } else {
                            $this->disenchantService->disenchantWithSkill($character->refresh(), $slot);
                        }
                    }
                } else {
                    event(new ServerMessageEvent($event->character->user, 'gained_item', $item->affix_name, route('game.items.item', [
                        'item' => $item
                    ]), $item->id));
                }


            }
        } else {
            event(new ServerMessageEvent($event->character->user, 'inventory_full'));
        }
    }
}
