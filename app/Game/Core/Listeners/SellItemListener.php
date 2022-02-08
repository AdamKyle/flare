<?php

namespace App\Game\Core\Listeners;

use App\Flare\Models\Item;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\SellItemEvent;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Services\EnchantingService;
use Facades\App\Flare\Calculators\SellItemCalculator;

class SellItemListener
{

    private $enchantingService;

    public function __construct(EnchantingService $enchantingService) {
        $this->enchantingService = $enchantingService;
    }

    public function handle(SellItemEvent $event)
    {
        $item = $event->inventorySlot->item;

        $event->character->gold += SellItemCalculator::fetchTotalSalePrice($item);
        $event->character->save();

        $event->inventorySlot->delete();

        $character = $event->character->refresh();

        $affixData = $this->enchantingService->fetchAffixes($character);

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        event(new UpdateTopBarEvent($event->character->refresh()));
    }


}
