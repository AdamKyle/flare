<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Events\BuyItemEvent;

class ShopController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function shopBuy() {

        return view('game.core.shop.buy', [
            'weapons'   => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'weapon')->get(),
            'armour'    => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->whereIn('type', [
                'body', 'leggings', 'sleeves', 'gloves', 'helmet', 'shield'
            ])->get(),

            'artifacts' => Item::with('artifactProperty')->where('type', 'artifact')->get(),
            'spells'    => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'spell')->get(),
            'rings'     => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'ring')->get(),
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

    }
}
