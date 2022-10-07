<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Shop\Services\ShopService;
use App\Http\Controllers\Controller;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Game\Shop\Requests\ShopSellValidation;
use App\Game\Core\Services\CharacterInventoryService;
use App\Flare\Models\Character;
use App\Game\Shop\Events\SellItemEvent;

class ShopController extends Controller {

    private $characterInventoryService;

    public function __construct(CharacterInventoryService $characterInventoryService) {
        $this->characterInventoryService = $characterInventoryService;
    }

    public function sellItem(ShopSellValidation $request, Character $character) {

        $inventorySlot = $character->inventory->slots->filter(function($slot) use($request) {
            return $slot->id === (int) $request->slot_id && !$slot->equipped;
        })->first();

        if (is_null($inventorySlot)) {
            return response()->json(['message' => 'Item not found.']);
        }

        $item         = $inventorySlot->item;

        if ($item->type === 'trinket') {
            return response()->json(['message' => 'The shop keeper will not accept this item (Trinkets cannot be sold to the shop).']);
        }

        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        $character = $character->refresh();

        event(new SellItemEvent($inventorySlot, $character));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message' => 'Sold: ' . $item->affix_name . ' for: ' . number_format($totalSoldFor) . ' gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
            ]
        ]);
    }

    public function sellAll(Character $character, ShopService $service) {

        $totalSoldFor = $service->sellAllItemsInInventory($character);

        $maxCurrencies = new MaxCurrenciesValue($character->gold + $totalSoldFor, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            $character->update([
                'gold' => MaxCurrenciesValue::MAX_GOLD,
            ]);
        } else {
            $character->update([
                'gold' => $character->gold + $totalSoldFor,
            ]);
        }

        $character = $character->refresh();

        $inventory = $this->characterInventoryService->setCharacter($character);

        if ($totalSoldFor === 0) {
            return response([
                'message' => 'Could not sell any items ...',
                'inventory' => [
                    'inventory' => $inventory->getInventoryForType('inventory'),
                ]
            ], 200);
        }

        event(new UpdateTopBarEvent($character));

        return response([
            'message' => 'Sold all your items for a total of: ' . number_format($totalSoldFor) . ' gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
            ]
        ]);
    }
}
