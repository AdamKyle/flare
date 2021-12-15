<?php

namespace App\Game\Core\Listeners;

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

        if (!is_null($gameMap->drop_chance_bonus)) {
            $gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $canGetDrop     = DropCheckCalculator::fetchDropCheckChance($event->monster, $lootingChance, $gameMapBonus, $event->adventure);

        if ($canGetDrop) {
            $drop = $this->randomItemDropBuilder
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

        if (!is_null($event->monster->quest_item_id)) {
            $canGetQuestItem = DropCheckCalculator::fetchQuestItemDropCheck($event->monster, $lootingChance, $gameMapBonus, $event->adventure);

            if ($canGetQuestItem) {
                $this->attemptToPickUpItem($event, $event->monster->questItem);

                event(new CharacterInventoryUpdateBroadCastEvent($event->character->user));
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
