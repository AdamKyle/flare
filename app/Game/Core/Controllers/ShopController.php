<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Events\BuyItemEvent;
use App\Game\Core\Events\SellItemEvent;

class ShopController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function shopBuy() {

        return view('game.core.shop.buy', [
            'weapons'   => Item::doesntHave('itemPrefix')->doesntHave('itemSuffix')->where('type', 'weapon')->get(),
            'armour'    => Item::doesntHave('itemPrefix')->doesntHave('itemSuffix')->whereIn('type', [
                'body', 'leggings', 'sleeves', 'gloves', 'helmet', 'shield'
            ])->get(),

            'artifacts' => Item::where('type', 'artifact')->get(),
            'spells'    => Item::doesntHave('itemPrefix')->doesntHave('itemSuffix')->where('type', 'spell')->get(),
            'rings'     => Item::doesntHave('itemPrefix')->doesntHave('itemSuffix')->where('type', 'ring')->get(),
        ]);
    }

    public function shopSell() {
        return view('game.core.shop.sell', [
            'inventory' => auth()->user()->character->inventory->slots->filter(function($slot) {
                return $slot->item->type !== 'quest' && !$slot->equipped;
            })->all(),
        ]);
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
        
        return redirect()->back()->with('success', 'Purchased: ' . $item->name . '.');
    }

    public function sell(Request $request) {
        
        $character     = auth()->user()->character;
        $inventorySlot = $character->inventory->slots->filter(function($slot) use($request) {
            return $slot->id === (int) $request->slot_id && !$slot->equipped;
        })->first();
        
        if (is_null($inventorySlot)) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        $name = $inventorySlot->item->name;

        event(new SellItemEvent($inventorySlot, $character));
        
        return redirect()->back()->with('success', 'Sold: ' . $name . '.');
    }
}
