<?php

namespace App\Game\Core\Listeners;

use Log;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\DropsCheckEvent;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Flare\Calculators\DropCheckCalculator;

class DropsCheckListener
{

    use CanHaveQuestItem;

    public function __construct(RandomItemDropBuilder $randomItemDropBuilder) {
        $this->randomItemDropBuilder = $randomItemDropBuilder;
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
            Log::info('Attempting to generate item ' . $event->character->name);
            $drop = $this->randomItemDropBuilder
                         ->setMonsterPlane($event->monster->gameMap->name)
                         ->setCharacterLevel($event->character->level)
                         ->setMonsterMaxLevel($event->monster->max_level)
                         ->generateItem();

            if (!is_null($drop)) {
                Log::info('Drop Name ' . $event->character->name . ' Name: ' . $drop->affix_name);
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
        Log::info('Attempting to pick up item ' . $event->character->name);
        if (!$event->character->isInventoryFull()) {
            Log::info('Can I have the item? ' . $event->character->name);
            if ($this->canHaveItem($event->character, $item)) {
                Log::info('I Can Has have the item! ' . $event->character->name);
                $event->character->inventory->slots()->create([
                    'item_id' => $item->id,
                    'inventory_id' => $event->character->inventory->id,
                ]);

                if ($item->type === 'quest') {
                    $message = $event->character->name . ' has found: ' . $item->affix_name;

                    broadcast(new GlobalMessageEvent($message));
                }

                event(new ServerMessageEvent($event->character->user, 'gained_item', $item->affix_name, route('game.items.item', [
                    'item' => $item
                ]), $item->id));
            }
        } else {
            \Log::info('No item ' . $event->character->name);
            event(new ServerMessageEvent($event->character->user, 'inventory_full'));
        }
    }
}
