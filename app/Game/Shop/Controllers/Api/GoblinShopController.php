<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Shop\Services\GoblinShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GoblinShopController extends Controller
{
    public function __construct(private readonly GoblinShopService $goblinShopService) {}

    public function fetchItems(Character $character): JsonResponse
    {
        $result = $this->goblinShopService->fetchItemsForShop($character);

        return response()->json($result);
    }

    public function purchaseItem(Character $character, Item $item): JsonResponse
    {
        $result = $this->goblinShopService->buyItem($character, $item);

        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
