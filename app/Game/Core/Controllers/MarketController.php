<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use League\Fractal\Manager;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MarketBoard;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use App\Game\Core\Traits\UpdateMarketBoard;


class MarketController extends Controller {

    use UpdateMarketBoard;

    private $manager;

    private $transformer;

    public function __construct(Manager $manager, MarketItemsTransfromer $transformer) {
        $this->middleware('auth');

        $this->middleware('is.character.dead');
        
        $this->middleware('is.character.adventuring');

        $this->middleware('is.character.at.location');

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
        
        $this->sendUpdate($this->transformer, $this->manager);
        
        return redirect()->to(route('game.market.sell'))->with('success', 'Item listed');
    }

    public function currentListings(Character $character) {

        if ($character->id !== auth()->user()->character->id) {
            return redirect()->to(route('game.current-listings', [
                'character' => auth()->user()->character->id
            ]))->with('error', 'You are not allowed to do that.');
        }

        $locked = MarketBoard::where('character_id', $character->id)->where('is_locked', true)->first();
        
        if (!is_null($locked)) {
            
            $locked->update([
                'is_locked' => false,
            ]);

            $this->sendUpdate($this->transformer, $this->manager);
        }

        

        return view('game.core.market.current-listings', [
            'character' => $character
        ]);
    }

    public function editCurrentListings(MarketBoard $marketBoard) {
        if (auth()->user()->character->id !== $marketBoard->character_id) {
            return redirect()->back()->with('error', 'You are not allowed to do that.');
        }

        if (!$marketBoard->is_locked) {
            $marketBoard->update([
                'is_locked' => true,
            ]);

            $this->sendUpdate($this->transformer, $this->manager);
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

        $marketBoard->update(array_merge($request->all(), [
            'is_locked' => false,
        ]));

        $this->sendUpdate($this->transformer, $this->manager);

        return redirect()->to(route('game.current-listings', [
            'character' => auth()->user()->character->id
        ]))->with('success', 'Listing for: ' . $marketBoard->item->affix_name . ' updated.');
    }

    public function delist(Request $request, MarketBoard $marketBoard) {
        $character = auth()->user()->character;

        if ($character->id !== $marketBoard->character_id) {
            return redirect()->back()->with('error', 'You are not allowed to do that.');
        }

        $itemName = $marketBoard->item->affix_name;

        $marketBoard->delete();

        $this->sendUpdate($this->transformer, $this->manager);

        return redirect()->back()->with('success', 'Delisted: ' . $itemName);
    }
}