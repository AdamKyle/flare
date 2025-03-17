<?php

namespace App\Http\Controllers\Api;

use App\Flare\Models\Item;
use App\Flare\Transformers\ItemTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class ItemsController extends Controller
{
    private ItemTransformer $itemTransformer;

    private Manager $manager;

    public function __construct(ItemTransformer $itemTransformer, Manager $manager)
    {
        $this->itemTransformer = $itemTransformer;
        $this->manager = $manager;
    }

    public function fetchCraftableItems(Request $request)
    {
        $filter = $request->get('filter');
        $searchText = $request->get('search_text');

        $cache = Cache::get('crafting-table-data');

        if (!is_null($cache) && is_null($filter) && is_null($searchText)) {
            return response()->json([
                'items' => $cache,
            ]);
        }

        $items = Item::whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNull('specialty_type')
            ->doesntHave('appliedHolyStacks')
            ->doesntHave('sockets')
            ->where('can_craft', true);

        if (!is_null($filter)) {
            $items = $items->where('type', $filter);
        }

        if (!is_null($searchText)) {
            $items = $items->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchText) . '%']);
        }

        $items = $items->orderBy('cost', 'asc')->get();

        $itemsCollection = new Collection($items, $this->itemTransformer);
        $itemsCollection = $this->manager->createData($itemsCollection)->toArray();

        if (is_null($filter) && is_null($searchText)) {
            Cache::put('crafting-table-data', $itemsCollection);
        }

        return response()->json([
            'items' => $itemsCollection,
        ]);
    }


    public function fetchSpecificSet(Request $request)
    {
        $items = Item::whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->doesntHave('appliedHolyStacks')
            ->doesnthave('sockets')
            ->where('specialty_type', $request->specialty_type)
            ->get();

        $itemsCollection = new Collection($items, $this->itemTransformer);
        $itemsCollection = $this->manager->createData($itemsCollection)->toArray();

        return response()->json([
            'items' => $itemsCollection,
        ]);
    }
}
