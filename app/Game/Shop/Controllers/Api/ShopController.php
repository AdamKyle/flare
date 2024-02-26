<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Models\Item;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\CharacterInventory\Services\ComparisonService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Requests\ShopPurchaseMultipleValidation;
use App\Game\Shop\Services\ShopService;
use App\Http\Controllers\Controller;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Game\Shop\Requests\ShopSellValidation;
use App\Game\CharacterInventory\Services\CharacterInventoryService;
use App\Flare\Models\Character;
use App\Game\Shop\Events\SellItemEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller {

    private CharacterInventoryService $characterInventoryService;
    private ShopService $shopService;

    public function __construct(CharacterInventoryService $characterInventoryService, ShopService $shopService) {
        $this->characterInventoryService = $characterInventoryService;
        $this->shopService = $shopService;
    }

    public function fetchItemsForShop(Character $character): JsonResponse {
        return response()->json([
            'items'           => $this->shopService->getItemsForShop(),
            'gold'            =>  $character->gold,
            'inventory_count' => $character->getInventoryCount(),
            'inventory_max'   => $character->inventory_max,
            'is_merchant'     => $character->classType()->isMerchant(),
        ]);
    }

    public function shopCompare(Request $request, Character $character,
                                ComparisonService $comparisonService) {

        $viewData = $comparisonService->buildShopData($character, Item::where('name', $request->item_name)->first(), $request->item_type);

        return response()->json([
            'comparison_data' => $viewData,
        ]);
    }

    public function buy(Request $request, Character $character) {

        if ($character->gold === 0) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        $item = Item::find($request->item_id);

        if (is_null($item)) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        $cost = $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = floor($cost - $cost * 0.25);
        }

        if ($cost > $character->gold) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        if ($character->isInventoryFull()) {
            return redirect()->back()->with('error', 'Inventory is full. Please make room.');
        }

        event(new BuyItemEvent($item, $character));

        return response()->json([
            'message' => 'Purchased: ' . $item->affix_name . '.'
        ]);
    }

    public function buyMultiple(ShopPurchaseMultipleValidation $request, Character $character) {
        $item   = Item::find($request->item_id);
        $amount = $request->amount;

        if ($amount > $character->inventory_max || $character->isInventoryFull()) {
            return redirect()->back()->with('error', 'You cannot purchase more then you have inventory space.');
        }

        $cost = $amount * $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = $cost - $cost * 0.25;
        }

        if ($cost > $character->gold) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        $this->shopService->buyMultipleItems($character, $item, $cost, $amount);

        return redirect()->to(route('game.shop.buy', ['character' => $character->id]))->with('success', 'You purchased: ' . $amount . ' of ' . $item->name);
    }

    public function sellItem(ShopSellValidation $request, Character $character) {

        $inventorySlot = $character->inventory->slots->filter(function ($slot) use ($request) {
            return $slot->id === (int) $request->slot_id && !$slot->equipped;
        })->first();

        if (is_null($inventorySlot)) {
            return response()->json(['message' => 'Item not found.']);
        }

        $item         = $inventorySlot->item;

        if ($item->type === 'trinket' || $item->type === 'artifact') {
            return response()->json(['message' => 'The shop keeper will not accept this item (Trinkets/Artifacts cannot be sold to the shop).']);
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

        $newGold = $character->gold + $totalSoldFor;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $newGold,
        ]);

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
