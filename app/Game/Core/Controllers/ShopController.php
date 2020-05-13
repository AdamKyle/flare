<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;

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
                return $slot->item->type !== 'quest';
            })->all(),
        ]);
    }

    public function buy(Request $request, Character $character) {

    }

    public function sell(Request $request, Character $character) {

    }
}
