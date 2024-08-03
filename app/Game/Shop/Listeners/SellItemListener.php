<?php

namespace App\Game\Shop\Listeners;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Skills\Services\EnchantingService;
use Facades\App\Flare\Calculators\SellItemCalculator;

class SellItemListener
{
    private $enchantingService;

    public function __construct(EnchantingService $enchantingService)
    {
        $this->enchantingService = $enchantingService;
    }

    public function handle(SellItemEvent $event)
    {
        $item = $event->inventorySlot->item;

        $totalNewGold = $event->character->gold + SellItemCalculator::fetchSalePriceWithAffixes($item);

        if ($totalNewGold > MaxCurrenciesValue::MAX_GOLD) {
            $totalNewGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $event->character->update([
            'gold' => $totalNewGold,
        ]);

        $event->inventorySlot->delete();

        event(new UpdateCharacterCurrenciesEvent($event->character->refresh()));
    }
}
