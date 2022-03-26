<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;

class TrinketCraftingController extends Controller {

    private $trinketCraftingService;

    public function __construct(TrinketCraftingService $trinketCraftingService) {
        $this->trinketCraftingService = $trinketCraftingService;
    }

    public function fetchItemsToCraft(Character $character) {

        return response()->json($this->trinketCraftingService->fetchItemsToCraft($character));
    }

    public function craftTrinket(Character $character, Item $item) {
        event(new CraftedItemTimeOutEvent($character));

        return response()->json($this->trinketCraftingService->craft($character, $item));
    }
}
