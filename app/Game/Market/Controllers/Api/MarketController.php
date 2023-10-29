<?php

namespace App\Game\Market\Controllers\Api;

use App\Flare\Traits\IsItemUnique;
use App\Game\Core\Services\CharacterInventoryService;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Models\MarketBoard;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Market\Requests\ListPriceRequest;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Transformers\MarketItemsTransformer;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Market\Requests\ChangeItemTypeRequest;

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

        if ($minCost !== 0 && $minCost > $request->list_for) {
            return response()->json(['message' => 'No! The minimum selling price is: ' . number_format($minCost) . ' Gold.'], 422);
        }

        $listPrice = $request->list_for;

        if ($listPrice > MaxCurrenciesValue::MAX_GOLD) {
            $listPrice = MaxCurrenciesValue::MAX_GOLD;
        }

        MarketBoard::create([
            'character_id' => auth()->user()->character->id,
            'item_id'      => $slot->item->id,
            'listed_price' => $listPrice,
        ]);

        $itemName = $slot->item->affix_name;

        $slot->delete();

        $this->sendUpdate($this->transformer, $this->manager);

        $inventory = $this->characterInventoryService->setCharacter($character->refresh());

        return response()->json([
            'message'   => 'Listed: ' . $itemName . ' For: ' . number_format($listPrice) . ' Gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
                'usable_items' => $inventory->getInventoryForType('usable_items'),
            ]
        ]);
    }
}
