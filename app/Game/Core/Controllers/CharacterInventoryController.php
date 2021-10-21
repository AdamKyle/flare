<?php

namespace App\Game\Core\Controllers;

use App\Flare\Services\BuildCharacterAttackTypes;
use Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\User;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Services\InventorySetService;
use App\Game\Core\Services\EquipItemService;
use App\Game\Core\Exceptions\EquipItemException;
use App\Game\Core\Requests\ComparisonValidation;
use App\Game\Core\Requests\EquipItemValidation;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Values\ValidEquipPositionsValue;

class CharacterInventoryController extends Controller {

    private $equipItemService;

    private $characterTransformer;

    private $buildCharacterAttackTypes;

    private $manager;

    public function __construct(EquipItemService $equipItemService, BuildCharacterAttackTypes $buildCharacterAttackTypes, CharacterAttackTransformer $characterTransformer, Manager $manager) {

        $this->equipItemService     = $equipItemService;
        $this->characterTransformer = $characterTransformer;
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
        $this->manager              = $manager;

        $this->middleware('auth');

        $this->middleware('is.character.dead');

        $this->middleware('is.character.adventuring');
    }

    public function compare(
        ComparisonValidation $request,
        ValidEquipPositionsValue $validPositions,
        CharacterInventoryService $characterInventoryService,
        Character $character
    ) {

        $itemToEquip = InventorySlot::find($request->slot_id);

        if (is_null($itemToEquip)) {
            return redirect()->back()->with('error', 'Item not found in your inventory.');
        }

        $type = $request->item_to_equip_type;

        if ($type === 'spell-healing' || $type === 'spell-damage') {
            $type = 'spell';
        }

        $service = $characterInventoryService->setCharacter($character)
                                             ->setInventorySlot($itemToEquip)
                                             ->setPositions($validPositions->getPositions($itemToEquip->item))
                                             ->setInventory($type);

        $viewData = [
            'details'     => [],
            'itemToEquip' => $itemToEquip->item,
            'type'        => $service->getType($itemToEquip->item, $request->has('item_to_equip_type') ? $type : null),
            'slotId'      => $itemToEquip->id,
            'characterId' => $character->id,
            'bowEquipped' => false,
            'setEquipped' => false,
            'setIndex'    => 0,
        ];

        if ($service->inventory()->isNotEmpty()) {
            $setEquipped = $character->inventorySets()->where('is_equipped', true)->first();


            $hasSet   = !is_null($setEquipped);
            $setIndex = !is_null($setEquipped) ? $character->inventorySets->search(function($set) {return $set->is_equipped; }) + 1 : 0;

            $viewData = [
                'details'      => $this->equipItemService->setRequest($request)->getItemStats($itemToEquip->item, $service->inventory(), $character),
                'itemToEquip'  => $itemToEquip->item,
                'type'         => $service->getType($itemToEquip->item, $request->has('item_to_equip_type') ? $type : null),
                'slotId'       => $itemToEquip->id,
                'slotPosition' => $itemToEquip->position,
                'characterId'  => $character->id,
                'bowEquipped'  => $this->equipItemService->isBowEquipped($itemToEquip->item, $service->inventory()),
                'setEquipped'  => $hasSet,
                'setIndex'     => $setIndex,
            ];
        }

        Cache::put($character->user->id . '-compareItemDetails' . $itemToEquip->id, $viewData, now()->addMinutes(10));

        return redirect()->to(route('game.inventory.compare-items', ['user' => $character->user, 'slot' => $itemToEquip->id]));
    }

    public function compareItem(Request $request, User $user) {
        if (!$request->has('slot')) {
            return redirect()->route('game.character.sheet')->with('error', 'You are not allowed to do that.');
        }

        if (!Cache::has($user->id . '-compareItemDetails' . $request->slot)) {
            return redirect()->route('game.character.sheet')->with('error', 'Item comparison expired.');
        }

        return view('game.character.equipment', Cache::get($user->id . '-compareItemDetails' . $request->slot));
    }

    public function equipItem(EquipItemValidation $request, Character $character) {
        try {
            $item = $this->equipItemService->setRequest($request)
                                           ->setCharacter($character)
                                           ->equipItem();

            $this->updateCharacterAttakDataCache($character);

            event(new CharacterInventoryUpdateBroadCastEvent($character->user));

            return redirect()->to(route('game.character.sheet'))->with('success', $item->affix_name . ' Equipped.');

        } catch(EquipItemException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function unequipItem(Request $request, Character $character, InventorySetService $inventorySetService) {
        if ($request->inventory_set_equipped) {
            $inventorySet = $character->inventorySets()->where('is_equipped', true)->first();
            $inventoryIndex = $character->inventorySets->search(function($set) { return $set->is_equipped; });

            $inventorySetService->unEquipInventorySet($inventorySet);

            return redirect()->back()->with('success', 'Unequipped Set ' . $inventoryIndex + 1 . '.');
        }

        $foundItem = $character->inventory->slots->find($request->item_to_remove);

        if (is_null($foundItem)) {
            return redirect()->back()->with('error', 'No item found to be equipped.');
        }

        $foundItem->update([
            'equipped' => false,
            'position' => null,
        ]);

        event(new UpdateTopBarEvent($character));

        $this->updateCharacterAttakDataCache($character);

        return redirect()->back()->with('success', 'Unequipped item.');
    }

    protected function updateCharacterAttakDataCache(Character $character) {
        $this->buildCharacterAttackTypes->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), $this->characterTransformer);

        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateAttackStats($characterData, $character->user));
    }
}
