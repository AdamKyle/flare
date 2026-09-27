<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Game\Character\CharacterInventory\Services\CharacterCurrencyCacheService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CharacterCurrencyCacheController extends Controller
{
    /**
     * @param CharacterCurrencyCacheService $characterCurrencyCacheService
     */
    public function __construct(
        private readonly CharacterCurrencyCacheService $characterCurrencyCacheService,
    ) {}

    /**
     * Withdraw currency from an owned Alchemy Bag Compensation Cache.
     *
     * @param Character $character
     * @param AlchemyBagSlot $alchemyBagSlot
     * @return JsonResponse
     */
    public function use(Character $character, AlchemyBagSlot $alchemyBagSlot): JsonResponse
    {
        $result = $this->characterCurrencyCacheService->useCache($character, $alchemyBagSlot);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
