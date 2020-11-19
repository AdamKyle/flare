<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Game\Core\Events\BuyItemEvent;
use App\Game\Core\Events\SellItemEvent;

class ShopController extends Controller {

    public function __construct() {
        $this->middleware('auth');
        $this->middleware('is.character.dead');
        $this->middleware('is.character.adventuring');
    }

    public function shopBuy() {

        $character = auth()->user()->character;

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

        return view('game.core.shop.buy', [
            'isLocation' => !is_null($location),
            'gold'       => auth()->user()->character->gold,
        ]);
    }

    public function shopSell() {
        $character = auth()->user()->character;

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();
        
        return view('game.core.shop.sell', [
            'isLocation' => !is_null($location),
            'gold'       => auth()->user()->character->gold,
        ]);
    }

    public function shopSellAll() {
        $character = auth()->user()->character;

        $itemsToSell = $character->inventory->slots->filter(function($slot) {
            return !$slot->equipped && $slot->item->type !== 'quest';
        })->all();

        $itemsToSell = collect($itemsToSell);

        if ($itemsToSell->isEmpty()) {
            return redirect()->back()->with('error', 'You have nothing that you can sell.');
        }

        $totalSoldFor = 0;

        foreach ($itemsToSell as $itemSlot) {
            $totalSoldFor += SellItemCalculator::fetchTotalSalePrice($itemSlot->item);

            event(new SellItemEvent($itemSlot, $character));
        }

        return redirect()->back()->with('success', 'Sold all your unequipped items for a total of: ' . $totalSoldFor . ' gold.');
    }

    public function shopBuyBulk(Request $request) {
        $character = auth()->user()->character;

        if ($character->gold === 0) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        $items = Item::findMany($request->items);

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'No items could be found. Did you select any?');
        }

        foreach ($items as $item) {
            $character = $character->refresh();

            if ($item->cost > $character->gold) {
                return redirect()->back()->with('error', 'You do not have enough gold to buy: ' . $item->name . '. Anything before this item in the list was purchased.');
            }

            event(new BuyItemEvent($item, $character));
        }

        return redirect()->back()->with('success', 'Puchased all selected items.');

    }

    public function buy(Request $request) {
        $character = auth()->user()->character;

        if ($character->gold === 0) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        $item = Item::find($request->item_id);

        if (is_null($item)) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        if ($item->cost > $character->gold) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        event(new BuyItemEvent($item, $character));
        
        return redirect()->back()->with('success', 'Purchased: ' . $item->affix_name . '.');
    }

    public function sell(Request $request) {
        
        $character     = auth()->user()->character;
        $inventorySlot = $character->inventory->slots->filter(function($slot) use($request) {
            return $slot->id === (int) $request->slot_id && !$slot->equipped;
        })->first();
        
        if (is_null($inventorySlot)) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        $item = $inventorySlot->item;

        event(new SellItemEvent($inventorySlot, $character));

        $totalSoldFor = SellItemCalculator::fetchTotalSalePrice($item);
        
        return redirect()->back()->with('success', 'Sold: ' . $item->affix_name . ' for: ' . $totalSoldFor . ' gold.');
    }

    public function shopSellBulk(Request $request) {
        $character = auth()->user()->character;

        $inventorySlots = $character->inventory->slots()->findMany($request->slots);

        if ($inventorySlots->isEmpty()) {
            return redirect()->back()->with('error', 'No items could be found. Did you select any?');
        }

        $totalSoldFor = 0;

        foreach ($inventorySlots as $slot) {
            $character = $character->refresh();

            event(new SellItemEvent($slot, $character));

            $totalSoldFor += SellItemCalculator::fetchTotalSalePrice($slot->item);
        }

        return redirect()->back()->with('success', 'Sold selected items for: ' . $totalSoldFor . ' gold.');
    }
}
