<?php

namespace App\Game\Core\Listeners;

use App\Flare\Models\Item;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\SellItemEvent;
use Facades\App\Flare\Calculators\SellItemCalculator;

class SellItemListener
{

    public function __construct() {}

    public function handle(SellItemEvent $event)
    {
        $item = $event->inventorySlot->item;
        
        $event->character->gold += SellItemCalculator::fetchTotalSalePrice($item);
        $event->character->save();

        $event->inventorySlot->delete();

        $event->character->refresh();

        event(new UpdateTopBarEvent($event->character));
    }


}
