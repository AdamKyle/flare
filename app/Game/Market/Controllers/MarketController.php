<?php

namespace App\Game\Market\Controllers;

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
        $this->manager     = $manager;
        $this->transformer = $transformer;
    }

    public function index() {
        return view('game.core.market.market');
    }

    public function sell() {
        return view('game.core.market.sell');
    }

    public function currentListings(Character $character) {
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
        $character = auth()->user()->character;

        if ($character->id !== $marketBoard->character_id) {
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
        $character = auth()->user()->character;

        $request->validate([
            'listed_price' => 'required|integer'
        ]);

        if ($character->id !== $marketBoard->character_id) {
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

        if (!($character->inventory_max > $character->inventory->slots->count())) {
            return redirect()->back()->with('error', 'You don\'t have the inventory space to delist the item.');
        }

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $marketBoard->item->id,
        ]);

        $itemName = $marketBoard->item->affix_name;

        $marketBoard->delete();

        $this->sendUpdate($this->transformer, $this->manager);

        return redirect()->back()->with('success', 'Delisted: ' . $itemName);
    }
}
