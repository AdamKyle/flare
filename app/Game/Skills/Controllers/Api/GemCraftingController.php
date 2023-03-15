<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Skills\Requests\GemCraftingValidation;
use App\Game\Skills\Services\GemService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;

class GemCraftingController extends Controller {

    private GemService $gemService;

    public function __construct(GemService $gemService) {
        $this->gemService = $gemService;
    }

    public function getCraftableItems(Character $character) {
        return response()->json($this->gemService->getCraftableTiers($character));
    }

    public function craftGem(Character $character, GemCraftingValidation $request) {

        $result = $this->gemService->generateGem($character, $request->tier);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
