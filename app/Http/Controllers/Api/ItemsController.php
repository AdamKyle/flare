<?php

namespace App\Http\Controllers\Api;


use App\Flare\Models\Item;
use App\Flare\Transformers\ItemTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class ItemsController extends Controller {

    private ItemTransformer $itemTransformer;
    private Manager $manager;

    public function __construct(ItemTransformer $itemTransformer, Manager $manager) {
        $this->itemTransformer = $itemTransformer;
        $this->manager = $manager;
    }

    public function fetchCraftableItems() {
        $items = Item::whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNull('specialty_type')
            ->get();

        $itemsCollection = new Collection($items, $this->itemTransformer);
        $itemsCollection = $this->manager->createData($itemsCollection)->toArray();

        return response()->json([
            'items' => $itemsCollection
        ]);
    }

    public function fetchSpecificSet(Request $request) {
        $items = Item::whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->where('specialty_type', $request->specialty_type)
            ->get();

        $itemsCollection = new Collection($items, $this->itemTransformer);
        $itemsCollection = $this->manager->createData($itemsCollection)->toArray();

        return response()->json([
            'items' => $itemsCollection
        ]);
    }
}
