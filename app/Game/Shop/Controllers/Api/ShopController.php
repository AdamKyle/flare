<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Shop\Requests\ShopPurchaseMultipleValidation;
use App\Game\Shop\Requests\ShopReplaceItemValidation;
use App\Game\Shop\Services\ShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * @param ShopService $shopService
     * @param ComparisonService $comparisonService
     */
    public function __construct(
        private readonly ShopService $shopService,
        private readonly ComparisonService $comparisonService,
    ) {}

    /**
     * Paginate the Shop's purchasable Items for the character.
     *
     * @param PaginationRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchItemsForShop(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json($this->shopService->getItemsForShop(
            $character,
            $request->input('filters.type'),
            $request->input('search_text'),
            $request->input('filters.sort_cost'),
            $request->integer('per_page'),
            $request->integer('page'),
        ));
    }

    /**
     * Build the Shop item-comparison data for the character.
     *
     * @param Request $request
     * @param Character $character
     * @return JsonResponse
     */
    public function shopCompare(Request $request, Character $character): JsonResponse
    {
        $item = Item::where('name', $request->input('item_name'))->first();

        if (is_null($item)) {
            return response()->json([
                'message' => 'Item not found.',
            ], 422);
        }

        return response()->json($this->comparisonService->buildShopData($character, $item));
    }

    /**
     * Purchase a single Shop Item for the character.
     *
     * @param Request $request
     * @param Character $character
     * @return JsonResponse
     */
    public function buy(Request $request, Character $character): JsonResponse
    {
        return $this->resultResponse($this->shopService->purchaseItem($character, Item::find($request->input('item_id'))));
    }

    /**
     * Purchase multiple stacked units of a Shop Item for the character.
     *
     * @param ShopPurchaseMultipleValidation $request
     * @param Character $character
     * @return JsonResponse
     */
    public function buyMultiple(ShopPurchaseMultipleValidation $request, Character $character): JsonResponse
    {
        return $this->resultResponse($this->shopService->purchaseMultiple(
            $character,
            Item::find($request->integer('item_id')),
            $request->integer('amount'),
        ));
    }

    /**
     * Purchase a Shop Item and equip it in place of a currently equipped item for the character.
     *
     * @param ShopReplaceItemValidation $request
     * @param Character $character
     * @return JsonResponse
     */
    public function buyAndReplace(ShopReplaceItemValidation $request, Character $character): JsonResponse
    {
        return $this->resultResponse($this->shopService->purchaseAndReplace(
            $character,
            Item::find($request->integer('item_id_to_buy')),
            $request->only(['position', 'slot_id', 'equip_type']),
        ));
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
