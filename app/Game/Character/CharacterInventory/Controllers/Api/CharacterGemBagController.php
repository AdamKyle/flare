<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GemBagSlot;
use App\Game\Character\CharacterInventory\Services\CharacterGemBagService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CharacterGemBagController extends Controller
{
    private CharacterGemBagService $characterGemBagService;

    public function __construct(CharacterGemBagService $characterGemBagService)
    {

        $this->characterGemBagService = $characterGemBagService;
    }

    public function getGemSlots(Character $character): JsonResponse
    {

        $result = $this->characterGemBagService->getGems($character);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    public function getGem(Character $character, GemBagSlot $gemBagSlot): JsonResponse
    {
        $result = $this->characterGemBagService->getGemData($character, $gemBagSlot);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
