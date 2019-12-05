<?php

namespace App\Game\Core\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterInventoryTransformer;
use App\Game\Core\Services\EquipItemService;

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
            'equipment' => $character->equippedItems->load(['item', 'item.itemAffixes', 'item.artifactProperty']),
        ], 200);
    }

    public function equipItem(Request $request, EquipItemService $equipItemService, Character $character) {

        $request->validate([
            'type'       => 'required',
            'item_id'    => 'required',
            'equip_type' => 'required',
        ]);

        return $equipItemService->setRequest($request)
                                ->setCharacter($character)
                                ->equipItem();
    }
}
