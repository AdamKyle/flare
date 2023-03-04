<?php

namespace App\Game\Shop\Listeners;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Services\EnchantingService;

class BuyItemListener {

    public function handle(BuyItemEvent $event) {
        $cost = $event->item->cost;

        if ($event->character->classType()->isMerchant()) {
            $cost = $cost - $cost * 0.25;
        }

        $event->character->gold = $event->character->gold - $cost;
        $event->character->save();

        $event->character->inventory->slots()->create([
            'inventory_id' => $event->character->inventory->id,
            'item_id'      => $event->item->id,
        ]);

        $character = $event->character->refresh();

        event(new UpdateTopBarEvent($character));
    }
}
