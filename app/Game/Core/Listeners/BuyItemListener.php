<?php

namespace App\Game\Core\Listeners;

use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\BuyItemEvent;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Services\EnchantingService;

class BuyItemListener
{

    private $enchantingService;

    public function __construct(EnchantingService $enchantingService) {
        $this->enchantingService = $enchantingService;
    }

    public function handle(BuyItemEvent $event)
    {
        $event->character->gold = $event->character->gold - $event->item->cost;
        $event->character->save();

        $event->character->inventory->slots()->create([
            'inventory_id' => $event->character->inventory->id,
            'item_id'      => $event->item->id,
        ]);

        $character = $event->character->refresh();

        $affixData = $this->enchantingService->fetchAffixes($character);

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character));
    }
}
