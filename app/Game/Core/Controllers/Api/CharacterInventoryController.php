<?php

namespace App\Game\Core\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Core\Services\CharacterInventoryService;

class CharacterInventoryController extends Controller {

    private $characterInventoryService;

    public function __construct(CharacterInventoryService $characterInventoryService) {

        $this->characterInventoryService = $characterInventoryService;
    }

    public function inventory(Character $character) {
        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json($inventory->getInventoryForApi(), 200);
    }
}
