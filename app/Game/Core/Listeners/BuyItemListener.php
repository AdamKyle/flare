<?php

namespace App\Game\Core\Listeners;

use App\Flare\Models\Item;
use App\Flare\Events\UpdateCharacterSheetEvent;
use App\Flare\Events\UpdateCharacterInventoryEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\BuyItemEvent;
use App\Game\Core\Events\UpdateShopInventoryBroadcastEvent;

class BuyItemListener
{

    public function __construct() {}

    public function handle(BuyItemEvent $event)
    {
        $event->character->gold = $event->character->gold - $event->item->cost;
        $event->character->save();

        $event->character->inventory->slots()->create([
            'inventory_id' => $event->character->inventory->id,
            'item_id'      => $event->item->id,
        ]);

        $event->character->refresh();

        event(new UpdateTopBarEvent($event->character));
        event(new UpdateCharacterInventoryEvent($event->character));
        event(new UpdateCharacterSheetEvent($event->character));

        $inventory = $event->character->inventory->slots->filter(function($slot) {
            return $slot->item->type !== 'quest';
        })->all();

        event(new UpdateShopInventoryBroadcastEvent($inventory, $event->character->user));
    }
}
