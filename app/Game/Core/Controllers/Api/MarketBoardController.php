<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Item as ItemModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Transformers\ItemTransfromer;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use Carbon\Carbon;

class MarketBoardController extends Controller {

    private $manager;

    private $transformer;

    public function __construct(Manager $manager, MarketItemsTransfromer $transformer) {
        $this->middleware('auth:api');

        $this->middleware('is.character.dead');
        
        $this->middleware('is.character.adventuring');

        $this->manager    = $manager;

        $this->transformer = $transformer;
    }

    public function index(Request $request) {
        $items = null;

        if ($request->has('item_id')) {
            $items = MarketBoard::join('items', function($join) use($request) {
                return $join->on('market_board.item_id', '=', 'items.id')
                            ->where('items.id', $request->item_id);
            })->where('market_board.is_locked', false)
              ->select('market_board.*')
              ->get();  
        } else if ($request->has('type')) {
            $items = MarketBoard::join('items', function($join) use($request) {
                return $join->on('market_board.item_id', '=', 'items.id')
                            ->where('items.type', $request->type);
            })->where('market_board.is_locked', false)
              ->select('market_board.*')
              ->get();            
        } else {
            $items = MarketBoard::where('is_locked', false)->get();
        }

        $items = new Collection($items, $this->transformer);
        $items = $this->manager->createData($items)->toArray();

        return response()->json([
            'items' => $items,
            'gold'  => auth()->user()->character->gold,
        ], 200);
    }

    public function fetchItemDetails(ItemModel $item, ItemTransfromer $itemTransfromer) {

        $item = new Item($item, $itemTransfromer);
        $item = $this->manager->createData($item)->toArray();

        return response()->json($item, 200);
    }

    public function purchase(Request $request, Character $character) {
        $request->validate([
            'market_board_id' => 'required'
        ]);

        $listing = MarketBoard::find($request->market_board_id);

        if (is_null($listing)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        if (!($character->inventory->slots()->count() < $character->inventory_max)) {
            return response()->json(['message' => 'Inventory is full.']);
        }

        $character->update([
            'gold' => $character->gold - ($listing->listed_price * 1.05),
        ]);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $listing->item_id,
        ]);

        MarketHistory::create([
            'item_id'  => $listing->item_id,
            'sold_for' => $listing->listed_price,
        ]);

        $listing->character->update([
            'gold' => $listing->character->gold + ($listing->listed_price - ($listing->listed_price * 0.05)),
        ]);

        event(new UpdateTopBarEvent($listing->character->refresh()));
        event(new UpdateTopBarEvent($character->refresh()));

        $message = 'Sold market listing: ' . $listing->item->affix_name . ' for: ' . ($listing->listed_price - ($listing->listed_price * 0.05)) . ' After fees (5% tax).';

        event(new ServerMessageEvent($listing->character->user, 'sold_item', $message));

        $listing->delete();
        
        $items = MarketBoard::all();
        $items = new Collection($items, $this->transformer);
        $items = $this->manager->createData($items)->toArray();
        
        $character = $character->refresh();

        event(new UpdateMarketBoardBroadcastEvent($character->user, $items, $character->gold));

        return response()->json([], 200);
    }

    public function history() {
        return response()->json([
            'labels' => MarketHistory::where('created_at', '>=', Carbon::today()->subDays(30))->get()->map(function($mh) {
                return $mh->created_at->format('y-m-d');
            }),
            'data'   => MarketHistory::where('created_at', '>=', Carbon::today()->subDays(30))->get()->pluck('sold_for'),
        ]);
    }
}