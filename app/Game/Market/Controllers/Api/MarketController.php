<?php

namespace App\Game\Market\Controllers\Api;

use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Flare\Models\Item;
use App\Flare\Transformers\ItemTransfromer;
use App\Game\Market\Requests\ChangeItemTypeRequest;
use App\Game\Market\Requests\ItemDetailsRequest;

class MarketController extends Controller {

    private $manager;

    private $transformer;

    public function __construct(Manager $manager, MarketItemsTransfromer $transformer) {
        $this->manager     = $manager;
        $this->transformer = $transformer;
    }

    public function index() {
        return response()->json([], 200);
    }

    public function fetchCharacterItems(ChangeItemTypeRequest $request, Character $character) {

        $slots = $character->inventory->slots()->join('items', function($join) use($request) {
            $join = $join->on('inventory_slots.item_id', '=', 'items.id');

            $join->where('items.market_sellable', true);

            if ($request->has('type')) {
                $join->where('items.type', $request->type);
            }
        })->get()->transform(function($slot) {
            $slot->name = $slot->item->affix_name;

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
}
