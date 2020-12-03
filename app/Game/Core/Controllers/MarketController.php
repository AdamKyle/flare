<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MarketBoard;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use App\Http\Controllers\Controller;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class MarketController extends Controller {

    private $manager;

    private $transfer;

    public function __construct(Manager $manager, MarketItemsTransfromer $transformer) {
        $this->middleware('auth');

        $this->middleware('is.character.dead');
        
        $this->middleware('is.character.adventuring');

        $this->manager     = $manager;
        $this->transformer = $transformer;
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
        
        $items = MarketBoard::all();
        $items = new Collection($items, $this->transformer);
        $items = $this->manager->createData($items)->toArray();

        event(new UpdateMarketBoardBroadcastEvent(auth()->user(), $items, auth()->user()->character->gold));
        
        return redirect()->to(route('game.market.sell'))->with('success', 'Item listed');
    }
}