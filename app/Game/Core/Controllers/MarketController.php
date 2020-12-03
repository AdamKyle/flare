<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Character;
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

    public function currentListings(Character $character) {

        if ($character->id !== auth()->user()->character->id) {
            return redirect()->to(route('game.current-listings', [
                'character' => auth()->user()->character->id
            ]))->with('error', 'You are not allowed to do that.');
        }

        return view('game.core.market.current-listings', [
            'character' => $character
        ]);
    }

    public function editCurrentListings(MarketBoard $marketBoard) {
        if (auth()->user()->character->id !== $marketBoard->character_id) {
            return redirect()->back()->with('error', 'You are not allowed to do that.');
        }

        return view('game.core.market.edit-current-listing', [
            'marketBoard' => $marketBoard
        ]);
    }

    public function updateCurrentListing(Request $request, MarketBoard $marketBoard) {
        $request->validate([
            'listed_price' => 'required|integer'
        ]);

        if (auth()->user()->character->id !== $marketBoard->character_id) {
            return redirect()->back()->with('error', 'You are not allowed to do that.');
        }

        if ($request->listed_price <= 0) {
            return redirect()->back()->with('error', 'Listed price cannot be below or equal to 0.');
        }

        $marketBoard->update($request->all());

        $items = MarketBoard::all();
        $items = new Collection($items, $this->transformer);
        $items = $this->manager->createData($items)->toArray();

        event(new UpdateMarketBoardBroadcastEvent(auth()->user(), $items, auth()->user()->character->gold));

        return redirect()->back()->with('success', 'Listing for: ' . $marketBoard->item->affix_name . ' updated.');
    }

    public function delist(Request $request, MarketBoard $marketBoard) {
        $character = auth()->user()->character;

        if ($character->id !== $marketBoard->character_id) {
            return redirect()->back()->with('error', 'You are not allowed to do that.');
        }

        $itemName = $marketBoard->item->affix_name;

        $marketBoard->delete();

        $items = MarketBoard::all();
        $items = new Collection($items, $this->transformer);
        $items = $this->manager->createData($items)->toArray();

        event(new UpdateMarketBoardBroadcastEvent(auth()->user(), $items, auth()->user()->character->gold));

        return redirect()->back()->with('success', 'Delisted: ' . $itemName);
    }
}