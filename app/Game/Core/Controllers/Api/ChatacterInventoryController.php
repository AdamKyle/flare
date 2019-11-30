<?php

namespace App\Game\Core\Controllers\Api;

use App\Http\Controllers\Controller;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterInventoryTransformer;

class CharacterInventoryController extends Controller {

    private $manager;

    private $characterInventoryTransformer;

    public function __construct(Manager $manager, CharacterInventoryTransformer $characterInventoryTransformer) {
        $this->middleware('auth:api');

        $this->manager                       = $manager;
        $this->characterInventoryTransformer = $characterInventoryTransformer;
    }

    public function inventory(Character $character) {
        $inventory = new Item($character->inventory, $this->characterInventoryTransformer);

        return response()->json([
            'inventory' => $this->manager->createData($inventory)->toArray(),
        ], 200);
    }
}
