<?php

namespace App\Game\Shop\Listeners;

use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Shop\Events\BuyItemEvent;

class BuyItemListener
{
    /**
     * Broadcast the character's updated currencies and inventory count after a completed Shop purchase.
     *
     * @param BuyItemEvent $event
     * @return void
     */
    public function handle(BuyItemEvent $event): void
    {
        event(new UpdateCharacterCurrenciesEvent($event->character));

        event(new UpdateCharacterInventoryCountEvent($event->character));
    }
}
