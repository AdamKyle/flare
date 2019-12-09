<?php

namespace App\Game\Core\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterInventoryTransformer;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Events\UpdateCharacterSheetEvent;
use App\Flare\Events\UpdateCharacterInventoryEvent;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Values\MaxDamageForItemValue;
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

        $equipment = $character->equippedItems->load([
                'item', 'item.itemAffixes', 'item.artifactProperty'
            ])->transform(function($equippedItem) {
                $equippedItem->actions          = null;
                $equippedItem->item->max_damage = resolve(MaxDamageForItemValue::class)
                                                    ->fetchMaxDamage($equippedItem->item);

                return $equippedItem;
            });

        return response()->json([
            'inventory'   => $this->manager->createData($inventory)->toArray(),
            'equipment'   => $equipment,
            'quest_items' => $character->inventory->questItemSlots->load('item', 'item.itemAffixes', 'item.artifactProperty'),
        ], 200);
    }

    public function equipItem(Request $request, EquipItemService $equipItemService, Character $character) {
        $request->validate([
            'position'   => 'required',
            'item_id'    => 'required',
            'equip_type' => 'required',
        ]);

        return $equipItemService->setRequest($request)
                                ->setCharacter($character)
                                ->equipItem();
    }

    public function unequipItem(Request $request, Character $character) {
        $item = $character->equippedItems->where('id', '=', $request->equipment_id)->first();

        if (is_null($item)) {
            return response()->json([
                'message' => 'Could not find a matching equipped item.',
            ], 422);
        }

        $name = $item->item->name;

        $item->delete();

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));
        event(new UpdateCharacterSheetEvent($character));
        event(new UpdateCharacterInventoryEvent($character));

        return response()->json([
            'message' => 'Unequipped ' . $name,
        ], 200);
    }

    public function destroyItem(Request $request, Character $character) {
        $item = $character->inventory->slots->where('item_id', '=', $request->item_id)->first();

        if (is_null($item)) {
            return response()->json([
                'message' => 'Could not find a matching item.',
            ], 422);
        }

        $name = $item->item->name;

        $item->delete();

        $character = $character->refresh();

        event(new UpdateCharacterInventoryEvent($character));

        return response()->json([
            'message' => 'Destroyed ' . $name,
        ], 200);
    }
}
