<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Shop\Requests\GoblinShopPurchaseRequest;
use App\Game\Shop\Services\GoblinShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GoblinShopController extends Controller
{
    /**
     * @param GoblinShopService $goblinShopService
     */
    public function __construct(private readonly GoblinShopService $goblinShopService) {}

    /**
     * Paginate the Items the Goblin Shop sells.
     *
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchItems(Character $character): JsonResponse
    {
        return response()->json($this->goblinShopService->fetchItemsForShop($character));
    }

    /**
     * Buy the requested amount of a Goblin Shop Item for the character.
     *
     * @param GoblinShopPurchaseRequest $request
     * @param Character $character
     * @param Item $item
     * @return JsonResponse
     */
    public function purchaseItem(GoblinShopPurchaseRequest $request, Character $character, Item $item): JsonResponse
    {
        $result = $this->goblinShopService->buyItem($character, $item, $request->integer('amount'));

        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
