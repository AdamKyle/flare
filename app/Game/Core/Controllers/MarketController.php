<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MarketBoard;
use App\Http\Controllers\Controller;

class MarketController extends Controller {

    public function __construct() {
        $this->middleware('auth');

        $this->middleware('is.character.dead');
        
        $this->middleware('is.character.adventuring');
    }

    public function index() {
        return view('game.core.market.market');
    }

    public function sell() {
        return view('game.core.market.sell');
    }

    public function list(Request $request, InventorySlot $slot) {
        if (!$request->has('sell_for')) {
            return redirect()->to(route('game.market.sell'))->with('error', 'How much are you trying to sell this for? Missing Sell for.');
        }

        MarketBoard::create([
            'character_id' => auth()->user()->character->id,
            'item_id'      => $slot->item->id,
            'listed_price' => $request->sell_for,
        ]);

        $slot->delete();
        
        return redirect()->to(route('game.market.sell'))->with('success', 'Item listed');
    }
}