<?php

namespace App\Game\Core\Listeners;

use App\Game\Core\Events\DropsCheckEvent;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Flare\Calculators\DropCheckCalculator;

class DropsCheckListener
{

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
            $drop = resolve(RandomItemDropBuilder::class)
                        ->setItemAffixes(ItemAffix::where('can_drop', true)->get())
                        ->generateItem($event->character);

            if (!is_null($drop->itemSuffix) || !is_null($drop->itemPrefix)) {
                $this->attemptToPickUpItem($event, $drop);
            }
        }

        if (!is_null($event->monster->quest_item_id)) {
            $canGetQuestItem = DropCheckCalculator::fetchQuestItemDropCheck($event->monster, $lootingChance, $gameMapBonus, $event->adventure);

            if ($canGetQuestItem) {
                $this->attemptToPickUpItem($event, $event->monster->questItem);
            }
        }
    }

    protected function attemptToPickUpItem(DropsCheckEvent $event, Item $item) {
        if (!$event->character->isInventoryFull()) {

            $doesntHave = $event->character->inventory->slots->filter(function ($slot) use ($item) {
                return $slot->item_id === $item->id && $item->type === 'quest';
            })->isEmpty();

            $hasCompletedQuest = $event->character->questsCompleted->filter(function($questCompleted) use ($item) {
                return $questCompleted->quest->item_id === $item->id;
            })->isEmpty();

            if ($doesntHave && $hasCompletedQuest) {
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
            event(new ServerMessageEvent($event->character->user, 'inventory_full'));
        }
    }
}
