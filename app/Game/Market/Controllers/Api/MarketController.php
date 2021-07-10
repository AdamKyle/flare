<?php

namespace App\Game\Market\Controllers\Api;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Item as ItemModel;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Market\Requests\HistoryRequest;
use App\Game\Market\Requests\ListPriceRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item as FractalItem;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Flare\Models\Item;
use App\Flare\Transformers\ItemTransfromer;
use App\Game\Market\Requests\ChangeItemTypeRequest;
use App\Game\Market\Requests\ItemDetailsRequest;
use League\Fractal\Resource\ResourceAbstract;

class MarketController extends Controller {

    use UpdateMarketBoard;

    private $manager;

    private $transformer;

    public function __construct(Manager $manager, MarketItemsTransfromer $transformer) {
        $this->manager     = $manager;
        $this->transformer = $transformer;
    }

    public function marketItems(ChangeItemTypeRequest $request) {
        if ($request->has('item_id')) {
            $items = MarketBoard::join('items', function($join) use($request) {
                return $join->on('market_board.item_id', '=', 'items.id')
                    ->where('items.id', $request->item_id);
            })->where('market_board.is_locked', false)
                ->select('market_board.*')
                ->get();
        } else if ($request->has('type')) {

            $items = MarketBoard::where('is_locked', false)->join('items', function($join) use($request) {
                return $join->on('market_board.item_id', '=', 'items.id')
                    ->where('items.type', $request->type);
            })->select('market_board.*')->get();
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

    public function listingDetails(Item $item, ItemTransfromer $itemTransfromer) {

        $item = new FractalItem($item, $itemTransfromer);
        $item = $this->manager->createData($item)->toArray();

        return response()->json($item, 200);
    }

    public function fetchCharacterItems(ChangeItemTypeRequest $request, Character $character) {

        $slots = $character->inventory->slots()->join('items', function($join) use($request) {
            $join = $join->on('inventory_slots.item_id', '=', 'items.id');

            $join->where('items.market_sellable', true);

            if ($request->has('type')) {
                $join->where('items.type', $request->type);
            }
        })->where('inventory_slots.equipped', false)->select('inventory_slots.id', 'items.name as item_name', 'items.id as item_id', 'items.type')->get()->transform(function($slot) {
            $item = Item::find($slot->item_id);

            $slot->name = $item->affix_name;

            $slot->item = $item;

            $slot->item->name = $item->affix_name;

            $slot->cost = SellItemCalculator::fetchSalePriceWithAffixes($slot->item);

            return $slot;
        });

        return response()->json([
            'slots' => $slots,
        ], 200);
    }

    public function fetchItemData(ItemDetailsRequest $request, ItemTransfromer $itemTransfromer) {
        $item = Item::find($request->item_id);

        if (is_null($item)) {
            return response()->json([], 404);
        }

        $item = new FractalItem($item, $itemTransfromer);
        $item = $this->manager->createData($item)->toArray();

        return response()->json($item, 200);
    }

    public function history(HistoryRequest $request) {
        $when = Carbon::today();

        if ($request->has('when')) {
            switch($request->when) {
                case 'today':
                    $when = Carbon::today();
                    break;
                case 'last 24 hours':
                    $when = Carbon::yesterday();
                    break;
                case '1 week':
                    $when = Carbon::today()->subWeek();
                    break;
                case '1 month':
                    $when = Carbon::today()->subMonth();
                    break;
                default:
                    break;
            }
        }

        if ($request->has('type')) {
            return response()->json([
                'labels' => MarketHistory::where('market_history.created_at', '>=', $when)->join('items', function($join) use($request) {
                    return $join->on('market_history.item_id', '=', 'items.id')
                        ->where('items.type', $request->type);
                })->select('market_history.*')->get()->map(function($mh) {
                    return $mh->created_at->format('y-m-d');
                }),
                'data'   => MarketHistory::where('market_history.created_at', '>=', $when)->join('items', function($join) use($request) {
                    return $join->on('market_history.item_id', '=', 'items.id')
                        ->where('items.type', $request->type);
                })->select('market_history.*')->get()->pluck('sold_for'),
            ]);
        }

        return response()->json([
            'labels' => MarketHistory::where('created_at', '>=', $when)->get()->map(function($mh) {
                return $mh->item->affix_name;
            }),
            'data'   => MarketHistory::where('created_at', '>=', $when)->get()->pluck('sold_for'),
        ]);
    }

    public function sellItem(ListPriceRequest $request, Character $character) {

        $slot = $character->inventory->slots()->find($request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'item is not found.'], 422);
        }

        MarketBoard::create([
            'character_id' => auth()->user()->character->id,
            'item_id'      => $slot->item->id,
            'listed_price' => $request->list_for,
        ]);

        $slot->delete();

        $this->sendUpdate($this->transformer, $this->manager);

        return response()->json([], 200);
    }

    public function purchase(Request $request, Character $character) {
        $request->validate([
            'market_board_id' => 'required'
        ]);

        $listing = MarketBoard::find($request->market_board_id);

        if (is_null($listing)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        if (!($character->inventory_max > $character->inventory->slots()->count())) {
            return response()->json(['message' => 'Inventory is full.'], 422);
        }

        $totalPrice = ($listing->listed_price * 1.05);

        if (!($character->gold > $totalPrice)) {
            return response()->json(['message' => 'You don\'t have the gold to purchase this item.'], 422);
        }

        $character->update([
            'gold' => $character->gold - $totalPrice,
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
}
