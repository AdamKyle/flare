<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Item as ItemModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use App\Flare\Models\MarketBoard;
use App\Flare\Transformers\ItemTransfromer;
use App\Flare\Transformers\MarketItemsTransfromer;

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

        if ($request->has('type')) {
            $items = MarketBoard::join('items', function($join) use($request) {
                return $join->on('market_board.item_id', '=', 'items.id')
                            ->where('items.type', $request->type);
            })->select('market_board.*')
              ->get();            
        } else {
            $items = MarketBoard::all();
        }

        $items = new Collection($items, $this->transformer);
        $items = $this->manager->createData($items)->toArray();

        return response()->json($items, 200);
    }

    public function fetchItemDetails(ItemModel $item, ItemTransfromer $itemTransfromer) {

        $item = new Item($item, $itemTransfromer);
        $item = $this->manager->createData($item)->toArray();

        return response()->json($item, 200);
    }
}