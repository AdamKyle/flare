<?php

namespace App\Game\CharacterInventory\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Flare\Models\Character;
use App\Flare\Models\GemBagSlot;
use App\Game\CharacterInventory\Services\CharacterGemBagService;
use App\Http\Controllers\Controller;


class CharacterGemBagController extends Controller {

    /**
     * @var CharacterGemBagService $characterGemBagService
     */
    private CharacterGemBagService $characterGemBagService;

    /**
     * @param CharacterGemBagService $characterGemBagService
     */
    public function __construct(CharacterGemBagService $characterGemBagService) {

        $this->characterGemBagService = $characterGemBagService;
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function getGemSlots(Character $character): JsonResponse {

        $result = $this->characterGemBagService->getGems($character);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Character $character
     * @param GemBagSlot $gemBagSlot
     * @return JsonResponse
     */
    public function getGem(Character $character, GemBagSlot $gemBagSlot): JsonResponse {
        $result = $this->characterGemBagService->getGemData($character, $gemBagSlot);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
