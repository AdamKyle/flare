<?php

namespace App\Game\Market\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\MarketBoard;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Market\Builders\MarketHistoryDailyPriceSeriesQueryBuilder;
use App\Game\Market\Enums\MarketHistorySecondaryFilter;
use App\Game\Market\Enums\MarketListingPriceSort;
use App\Game\Market\Requests\HistoryRequest;
use App\Game\Market\Requests\ListPriceRequest;
use App\Game\Market\Requests\MarketBuyAndReplaceRequest;
use App\Game\Market\Requests\MarketListingPriceRequest;
use App\Game\Market\Requests\MarketListingsRequest;
use App\Game\Market\Services\MarketAccessService;
use App\Game\Market\Services\MarketBoard as MarketBoardService;
use App\Game\Market\Services\MarketListingService;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    /**
     * @param MarketListingService $marketListingService
     * @param MarketBoardService $marketBoardService
     * @param MarketHistoryDailyPriceSeriesQueryBuilder $marketHistoryDailyPriceSeriesQueryBuilder
     * @param MarketAccessService $marketAccessService
     */
    public function __construct(
        private readonly MarketListingService $marketListingService,
        private readonly MarketBoardService $marketBoardService,
        private readonly MarketHistoryDailyPriceSeriesQueryBuilder $marketHistoryDailyPriceSeriesQueryBuilder,
        private readonly MarketAccessService $marketAccessService,
    ) {}

    /**
     * Report whether the character's user may currently use the Market.
     *
     * @param Character $character
     * @return JsonResponse
     */
    public function access(Character $character): JsonResponse
    {
        return response()->json([
            'can_access_market' => $this->marketAccessService->canAccess($character->user),
        ]);
    }

    /**
     * Paginate the unlocked Market listings.
     *
     * @param MarketListingsRequest $request
     * @return JsonResponse
     */
    public function marketItems(MarketListingsRequest $request): JsonResponse
    {
        return response()->json($this->marketListingService->browse(
            $request->string('search_text')->toString(),
            $request->input('filters.type'),
            MarketListingPriceSort::tryFrom($request->input('filters.sort_price') ?? ''),
            $request->integer('per_page'),
            $request->integer('page'),
        ));
    }

    /**
     * Return the current details of a listing for the authenticated character.
     *
     * @param Request $request
     * @param MarketBoard $marketBoard
     * @return JsonResponse
     */
    public function listing(Request $request, MarketBoard $marketBoard): JsonResponse
    {
        return $this->resultResponse($this->marketListingService->listingDetails($request->user()->character, $marketBoard));
    }

    /**
     * Compare a listing against the character's equipped Items.
     *
     * @param MarketBoard $marketBoard
     * @param Character $character
     * @return JsonResponse
     */
    public function compare(MarketBoard $marketBoard, Character $character): JsonResponse
    {
        return $this->resultResponse($this->marketBoardService->compare($character, $marketBoard));
    }

    /**
     * Buy a listing for the character.
     *
     * @param MarketBoard $marketBoard
     * @param Character $character
     * @return JsonResponse
     */
    public function buy(MarketBoard $marketBoard, Character $character): JsonResponse
    {
        return $this->resultResponse($this->marketBoardService->purchase($character, $marketBoard));
    }

    /**
     * Buy a listing and equip it in place of one of the character's equipped Items.
     *
     * @param MarketBuyAndReplaceRequest $request
     * @param MarketBoard $marketBoard
     * @param Character $character
     * @return JsonResponse
     */
    public function buyAndReplace(MarketBuyAndReplaceRequest $request, MarketBoard $marketBoard, Character $character): JsonResponse
    {
        return $this->resultResponse($this->marketBoardService->purchaseAndReplace(
            $character,
            $marketBoard,
            $request->string('position')->toString(),
            $request->integer('slot_id'),
            $request->string('equip_type')->toString(),
        ));
    }

    /**
     * Paginate the character's own listings.
     *
     * @param PaginationRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function currentListings(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json($this->marketListingService->ownedListings(
            $character,
            $request->integer('per_page'),
            $request->integer('page'),
        ));
    }

    /**
     * Lock an owned listing for editing.
     *
     * @param MarketBoard $marketBoard
     * @param Character $character
     * @return JsonResponse
     */
    public function beginEdit(MarketBoard $marketBoard, Character $character): JsonResponse
    {
        return $this->resultResponse($this->marketListingService->beginEdit($character, $marketBoard));
    }

    /**
     * Save a new price for an owned listing.
     *
     * @param MarketListingPriceRequest $request
     * @param MarketBoard $marketBoard
     * @param Character $character
     * @return JsonResponse
     */
    public function updateListing(MarketListingPriceRequest $request, MarketBoard $marketBoard, Character $character): JsonResponse
    {
        return $this->resultResponse($this->marketListingService->updatePrice($character, $marketBoard, $request->integer('listed_price')));
    }

    /**
     * Release the edit lock on an owned listing.
     *
     * @param MarketBoard $marketBoard
     * @param Character $character
     * @return JsonResponse
     */
    public function cancelEdit(MarketBoard $marketBoard, Character $character): JsonResponse
    {
        return $this->resultResponse($this->marketListingService->cancelEdit($character, $marketBoard));
    }

    /**
     * Remove an owned listing and return its Item to the character.
     *
     * @param MarketBoard $marketBoard
     * @param Character $character
     * @return JsonResponse
     */
    public function delist(MarketBoard $marketBoard, Character $character): JsonResponse
    {
        return $this->resultResponse($this->marketListingService->delist($character, $marketBoard));
    }

    /**
     * List an owned inventory Item on the Market for the character.
     *
     * @param ListPriceRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function sellItem(ListPriceRequest $request, Character $character): JsonResponse
    {
        return $this->resultResponse($this->marketListingService->listItem($character, $request->integer('slot_id'), $request->integer('list_for')));
    }

    /**
     * Return the daily sale price series for an Item type over the last 90 days.
     *
     * @param HistoryRequest $request
     * @return JsonResponse
     */
    public function fetchMarketHistoryForItem(HistoryRequest $request): JsonResponse
    {
        $builder = $this->marketHistoryDailyPriceSeriesQueryBuilder->setup($request->type, CarbonImmutable::now(), 90)->clearFilters();

        $filter = MarketHistorySecondaryFilter::tryFrom($request->input('filter') ?? '');

        if (! is_null($filter)) {
            $builder = $builder->addFilter($filter);
        }

        return response()->json($builder->fetchDataSet());
    }

    /**
     * Convert a service operation result into a JSON response using its status.
     *
     * @param array $result
     * @return JsonResponse
     */
    private function resultResponse(array $result): JsonResponse
    {
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
