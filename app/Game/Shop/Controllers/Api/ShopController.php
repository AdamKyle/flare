<?php

namespace App\Game\Shop\Controllers\Api;

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
            return redirect()->back()->with('error', 'Item not found.');
        }

        $item         = $inventorySlot->item;
        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        event(new SellItemEvent($inventorySlot, $character));

        $inventory = $this->characterInventoryService->setCharacter($character->refresh());

        return response([
            'message' => 'Sold: ' . $item->affix_name . ' for: ' . number_format($totalSoldFor) . ' gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
            ]
        ]);
    }
}
