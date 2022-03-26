<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Http\Controllers\Controller;

class TrinketCraftingController extends Controller {

    public function fetchItemsToCraft(Character $character) {
        return response()->json([]);
    }

    public function craftTrinket(Character $character, Item $item) {

    }
}
