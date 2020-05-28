<?php

namespace App\Game\Core\Listeners;

use App\Flare\Models\Item;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\SellItemEvent;

class SellItemListener
{

    public function __construct() {}

    public function handle(SellItemEvent $event)
    {
        $item = $event->inventorySlot->item;

        $goldGained = round($item->cost + ($item->cost - ($item->cost * 0.25))) + $this->costForAffixes($item);

        $event->character->gold += $goldGained;
        $event->character->save();

        $event->inventorySlot->delete();

        $event->character->refresh();

        event(new UpdateTopBarEvent($event->character));

        $inventory = $event->character->inventory->slots->filter(function($slot) {
            return $slot->item->type !== 'quest';
        })->all();
    }

    protected function costForAffixes(Item $item): int {
        if (is_null($item->itemSuffix) && is_null($item->itemPrefix)) {
            return 0;
        }

        // TODO: Add a better way of calculating this.
        return 100;
    }
}
