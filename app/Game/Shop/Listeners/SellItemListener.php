<?php

namespace App\Game\Shop\Listeners;

use App\Flare\Models\Item;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Shop\Events\SellItemEvent;
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

        $totalNewGold = $event->character->gold + SellItemCalculator::fetchTotalSalePrice($item);

        $maxCurrencies = new MaxCurrenciesValue($totalNewGold, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            $event->character->update([
                'gold' => MaxCurrenciesValue::MAX_GOLD,
            ]);
        } else {
            $event->character->update([
                'gold' => $totalNewGold,
            ]);
        }

        $event->inventorySlot->delete();

        event(new UpdateTopBarEvent($event->character->refresh()));
    }


}
