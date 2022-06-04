<?php

namespace App\Game\Market\Controllers;

use Cache;
use App\Flare\Models\Item;
use App\Flare\Traits\Controllers\ItemsShowInformation;
use App\Game\Core\Services\ComparisonService;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\MarketBoard;
use App\Flare\Transformers\MarketItemsTransformer;
use App\Game\Market\Services\MarketBoard as MarketBoardService;


class MarketController extends Controller {

    use ItemsShowInformation;

    private $manager;

    private $transformer;

    private $marketBoardService;

    public function __construct(Manager $manager, MarketItemsTransformer $transformer, MarketBoardService $marketBoardService) {
        $this->manager            = $manager;
        $this->transformer        = $transformer;
        $this->marketBoardService = $marketBoardService;
    }

    public function index() {
        return view('game.core.market.market');
    }

    public function sell() {
        return view('game.core.market.sell');
    }

    public function marketCompare(Request $request, Character $character, MarketBoard $marketBoard, ComparisonService $comparisonService) {

        if ($marketBoard->character_id === $character->id) {
            return redirect()->back()->with('error', 'You cannot compare your own listing.');
        }

        $viewData = $comparisonService->buildShopData($character, Item::find($request->item_id), $request->item_type);

        Cache::put('market-board-comparison-character-' . $character->id, $viewData, now()->addMinutes(10));

        return redirect()->to(route('game.market.view.comparison', ['character' => $character, 'marketBoard' => $marketBoard]));

    }

    public function viewItemComparison(Character $character, MarketBoard $marketBoard) {
        $cache = Cache::get('market-board-comparison-character-' . $character->id);

        if (is_null($cache)) {
            return redirect()->to(route('game.market'))->with('error', 'Comparison cache has expired. Please click compare again. Cache expires after 10 minutes');
        }

        return view('game.core.comparison.comparison', [
            'itemToEquip'  => $cache,
            'route'        => route('game.market.buy-and-replace', ['character' => $character->id]),
            'listingId'    => $marketBoard->id,
            'listingPrice' => $marketBoard->listed_price,
        ]);
    }

    public function buyAndReplace(Request $request, Character $character) {

        $listing = MarketBoard::find($request->market_board_id);

        if (is_null($listing)) {
            return response()->redirectToRoute('game.market')->with('error', 'Looks like someone got to that before you!');
        }

        if ($listing->character_id === $character->id) {
            return redirect()->back()->with('error', 'You cannot do that. You own this listing.');
        }

        if ($listing->is_locked) {
            return response()->redirectToRoute('game.market')->with('error', 'That item is not available at the moment. The owner might be adjusting the price or it\'s in the process of being sold.');
        } else {
            $listing->update(['is_locked' => true]);
        }

        if ($character->isInventoryFull()) {
            $listing->update(['is_locked' => false]);
            return response()->redirectToRoute('game.market')->with('error', 'Crap, your inventory is full. Don\'t worry it didn\'t cost you anything.');
        }

        $totalPrice = ($listing->listed_price * 1.05);

        if (!($character->gold >= $totalPrice)) {
            $listing->update(['is_locked' => false]);
            return redirect()->back()->with('error', 'Not enough gold. We add a 5% tax to the total price.');
        }

        $this->marketBoardService->buyAndReplaceItem($request, $character, $listing, $totalPrice);

        return response()->redirectToRoute('game.market')->with('success', 'Item purchased and equipped!');
    }

    public function buy(Request $request, Character $character) {
        $listing = MarketBoard::find($request->market_board_id);

        if (is_null($listing)) {
            return response()->redirectToRoute('game.market')->with('error', 'Looks like someone got to that before you!');
        }

        if ($listing->character_id === $character->id) {
            return redirect()->back()->with('error', 'You cannot do that. You own this listing.');
        }

        if ($listing->is_locked) {
            return response()->redirectToRoute('game.market')->with('error', 'That item is not available at the moment. The owner might be adjusting the price or it\'s in the process of being sold.');
        } else {
            $listing->update(['is_locked' => true]);
        }

        if ($character->isInventoryFull()) {
            return response()->redirectToRoute('game.market')->with('error', 'Crap, your inventory is full. Don\'t worry it didn\'t cost you anything.');
        }

        $totalPrice = ($listing->listed_price * 1.05);

        if (!($character->gold >= $totalPrice)) {
            return response()->json(['message' => 'Not enough gold. We add a 5% tax to the total price.'], 422);
        }

        $this->marketBoardService->buyItem($character, $listing, $totalPrice);

        return response()->redirectToRoute('game.market')->with('success', 'Item purchased!');
    }

    public function currentListings(Character $character) {
        $locked = MarketBoard::where('character_id', $character->id)->where('is_locked', true)->first();

        if (!is_null($locked)) {

            $locked->update([
                'is_locked' => false,
            ]);
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
        }

        return view('game.core.market.edit-current-listing', ['marketBoard' => $marketBoard]);
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

        return redirect()->to(route('game.current-listings', [
            'character' => auth()->user()->character->id
        ]))->with('success', 'Listing for: ' . $marketBoard->item->affix_name . ' updated.');
    }

    public function delist(MarketBoard $marketBoard) {
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

        return redirect()->back()->with('success', 'De listed: ' . $itemName);
    }
}
