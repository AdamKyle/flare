<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GemBagSlot;
use App\Game\Shop\Services\GemShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GemShopController extends Controller {

    private GemShopService $gemShopService;

    public function __construct(GemShopService $gemShopService) {
        $this->gemShopService = $gemShopService;
    }

    public function sellSingleGem(Character $character, GemBagSlot $slot): JsonResponse {
        $result = $this->gemShopService->sellGem($character, $slot->id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function sellAllGems(Character $character): JsonResponse {
        $result = $this->gemShopService->sellAllGems($character);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
