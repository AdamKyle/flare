<?php

namespace App\Game\Market\Controllers\Api;

use App\Flare\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Traits\IsItemUnique;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use App\Game\Core\Services\CharacterInventoryService;
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
use App\Flare\Transformers\MarketItemsTransformer;
use App\Flare\Models\Item;
use App\Flare\Transformers\ItemTransformer;
use App\Game\Market\Requests\ChangeItemTypeRequest;
use App\Game\Market\Requests\ItemDetailsRequest;

class  MarketController extends Controller {

    use UpdateMarketBoard, IsItemUnique;

    private $manager;

    private $transformer;

    private $characterInventoryService;

    public function __construct(Manager $manager, MarketItemsTransformer $transformer, CharacterInventoryService $characterInventoryService) {
        $this->manager                   = $manager;
        $this->transformer               = $transformer;
        $this->characterInventoryService = $characterInventoryService;
    }

    public function marketItems(ChangeItemTypeRequest $request) {
        $items = MarketBoard::where('is_locked', false)
            ->where('item_id', $request->item_id)
            ->select('market_board.*')
            ->get();

        $items = new Collection($items, $this->transformer);
        $items = $this->manager->createData($items)->toArray();

        return response()->json([
            'items' => $items,
            'gold'  => auth()->user()->character->gold,
        ], 200);
    }

    public function sellItem(ListPriceRequest $request, Character $character) {

        $slot = $character->inventory->slots()->find($request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'item is not found.'], 422);
        }

        $minCost = SellItemCalculator::fetchMinPrice($slot->item);

        if ( $minCost !== 0 && $minCost > $request->list_for) {
            return response()->json(['message' => 'No! The minimum selling price is: ' . number_format($minCost) . ' Gold.'], 422);
        }

        MarketBoard::create([
            'character_id' => auth()->user()->character->id,
            'item_id'      => $slot->item->id,
            'listed_price' => $request->list_for,
        ]);

        $itemName = $slot->item->affix_name;

        $slot->delete();

        $this->sendUpdate($this->transformer, $this->manager);

        $inventory = $this->characterInventoryService->setCharacter($character->refresh());

        return response()->json([
            'message'   => 'Listed: ' . $itemName . ' For: ' . number_format($request->list_for) . ' Gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
                'usable_items' => $inventory->getInventoryForType('usable_items'),
            ]
        ]);
    }
}
