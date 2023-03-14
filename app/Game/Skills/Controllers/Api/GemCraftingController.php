<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Skills\Services\GemService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;

class GemCraftingController extends Controller {

    private GemService $gemService;

    public function __construct(GemService $gemService) {
        $this->gemService = $gemService;
    }

    public function craftGem(Character $character) {

        $this->gemService->generateGem($character);

        return response()->json();
    }
}
