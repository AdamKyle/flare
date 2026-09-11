<?php

namespace App\Game\Market\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\MarketBoard;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Market\Builders\MarketHistoryDailyPriceSeriesQueryBuilder;
use App\Game\Market\Enums\MarketHistorySecondaryFilter;
use App\Game\Market\Requests\ChangeItemTypeRequest;
use App\Game\Market\Requests\HistoryRequest;
use App\Game\Market\Requests\ListPriceRequest;
use App\Game\Market\Transformers\MarketItemsTransformer;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Http\JsonResponse;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class MarketController extends Controller
{
    use ChecksAutomationRestrictions, UpdateMarketBoard;

    public function __construct(
        private readonly Manager $manager,
        private readonly MarketItemsTransformer $transformer,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly MarketHistoryDailyPriceSeriesQueryBuilder $marketHistoryDailyPriceSeriesQueryBuilder,
    ) {}

    public function marketItems(ChangeItemTypeRequest $request)
    {
        $items = MarketBoard::where('is_locked', false)
            ->where('item_id', $request->item_id)
            ->select('market_board.*')
            ->get();

        $items = new Collection($items, $this->transformer);
        $items = $this->manager->createData($items)->toArray();

        return response()->json([
            'items' => $items,
            'gold' => auth()->user()->character->gold,
        ]);
    }

    /**
     * List an owned Item on the Market Board for the given Character, rejecting Items that are not market sellable.
     *
     * @return JsonResponse
     */
    public function sellItem(ListPriceRequest $request, Character $character)
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if ($request->list_for < 1) {
            return response()->json(['message' => 'Listing price must be at least 1 Gold.'], 422);
        }

        $slot = $character->inventory->slots()->find($request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'item is not found.'], 422);
        }

        if (! $slot->item->market_sellable) {
            return response()->json(['message' => 'This item cannot be sold on the Market.'], 422);
        }

        $minCost = SellItemCalculator::fetchMinPrice($slot->item);

        if ($minCost !== 0 && $minCost > $request->list_for) {
            return response()->json(['message' => 'No! The minimum selling price is: '.number_format($minCost).' Gold.'], 422);
        }

        $listPrice = $request->list_for;

        if ($listPrice > CurrencyLimit::MAX_GOLD) {
            $listPrice = CurrencyLimit::MAX_GOLD;
        }

        MarketBoard::create([
            'character_id' => auth()->user()->character->id,
            'item_id' => $slot->item->id,
            'listed_price' => $listPrice,
        ]);

        $itemName = $slot->item->affix_name;

        $slot->delete();

        $this->sendUpdate($this->transformer, $this->manager);

        $inventory = $this->characterInventoryService->setCharacter($character->refresh());

        return response()->json([
            'message' => 'Listed: '.$itemName.' For: '.number_format($listPrice).' Gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
                'usable_items' => $inventory->getInventoryForType('usable_items'),
            ],
        ]);
    }

    public function fetchMarketHistoryForItem(HistoryRequest $request): JsonResponse
    {

        $builder = $this->marketHistoryDailyPriceSeriesQueryBuilder->setup($request->type, CarbonImmutable::now(), 90)->clearFilters();

        if ($request->has('filter')) {
            $type = MarketHistorySecondaryFilter::tryFrom($request->filter);

            $builder = $builder->addFilter($type);
        }

        return response()->json($builder->fetchDataSet());
    }
}
