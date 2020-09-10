<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Services\EquipItemService;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Exceptions\EquipItemException;
use App\Game\Core\Requests\ComparisonValidation;
use App\Game\Core\Requests\EquipItemValidation;
use App\Game\Core\Values\ValidEquipPositionsValue;

class CharacterInventoryController extends Controller {

    private $equipItemService;

    public function __construct(EquipItemService $equipItemService) {

        $this->equipItemService = $equipItemService;

        $this->middleware('auth');
        $this->middleware('is.character.dead')->only([
            'compare', 'equipItem', 'destroy'
        ]);
        $this->middleware('is.character.adventuring')->only([
            'compare', 'equipItem', 'destroy'
        ]);
    }

    public function index(Character $character) {
        $character = auth()->user()->character;
        $inventory = $character->inventory->slots;

        $equipped = $inventory->where('equipped', true)->load([
                'item', 'item.itemPrefix', 'item.itemSuffix'
            ])->transform(function($equippedItem) {
                $equippedItem->item->max_damage = $equippedItem->item->getTotalDamage();

                return $equippedItem;
            });

        $characterInfo = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return view('game.core.character.inventory', [
            'inventory' => $inventory->where('equipped', false)->all(),
            'equipped'  => $equipped,
            'questItems' => $character->inventory->questItemSlots->load('item'),
            'characterInfo' => [
                'maxAttack'  => $characterInfo->buildAttack(),
                'maxDefence' => $characterInfo->buildDefence(),
                'maxHeal'    => $characterInfo->buildHealFor(),
            ],
        ]);
    }

    public function compare(ComparisonValidation $request, ValidEquipPositionsValue $validPositions) {
        $character   = auth()->user()->character;
        $itemToEquip = InventorySlot::find($request->slot_id);

        if (is_null($itemToEquip)) {
            return redirect()->back()->with('error', 'Item not found in your inventory.');
        }

        $positions = $validPositions->getPositions($itemToEquip->item);
        
        if (empty($positions)) {
            $inventory = $character->inventory->slots->filter(function($slot) use($request) {
                return $slot->item->type === $request->item_to_equip_type && $slot->equipped;
            });
        } else {
            $inventory = $character->inventory->slots->filter(function ($slot) use ($positions) {
                return in_array($slot->position, $positions) && $slot->equipped;
            });
        }

        $slotId        = $itemToEquip->id;
        $slotPosition  = $itemToEquip->position;
        $itemToEquip   = $itemToEquip->item->load(['itemPrefix', 'itemSuffix', 'slot']);

        if ($request->has('item_to_equip_type')) {
            $type = $this->fetchType($request->item_to_equip_type);
        } else {
            $type = $itemToEquip->crafting_type;
        }
        
        if ($inventory->isEmpty()) {
            return view('game.core.character.equipment-compare', [
                'details'     => [],
                'itemToEquip' => $itemToEquip,
                'type'        => $type,
                'slotId'      => $slotId,
            ]);
        }
        
        return view('game.core.character.equipment-compare', [
            'details'      => $this->equipItemService->setRequest($request)->getItemStats($itemToEquip, $inventory),
            'itemToEquip'  => $itemToEquip,
            'type'         => $type,
            'slotId'       => $slotId,
            'slotPosition' => $slotPosition
        ]);
    }

    public function equipItem(EquipItemValidation $request) {
        try {
            $item = $this->equipItemService->setRequest($request)
                                   ->setCharacter(auth()->user()->character)
                                   ->equipItem();

            return redirect()->to(route('game.character.inventory'))->with('success', $item->name . ' Equipped.');

        } catch(EquipItemException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function unequipItem(Request $request) {
        $character = auth()->user()->character;

        $foundItem = $character->inventory->slots->find($request->item_to_remove);

        if (is_null($foundItem)) {
            return redirect()->back()->with('error', 'No item found to be equipped.');
        }

        $foundItem->update([
            'equipped' => false,
            'position' => null,
        ]);

        event(new UpdateTopBarEvent($character));

        return redirect()->back()->with('success', 'Unequipped item.');
    }

    public function unequipAll(Request $request) {
        $character = auth()->user()->character;

        $character->inventory->slots->each(function($slot) {
            $slot->update([
                'equipped' => false,
                'position' => null,
            ]);
        });

        event(new UpdateTopBarEvent($character->refresh()));

        return redirect()->back()->with('success', 'All items have been removed.');
    }

    public function destroy(Request $request) {
        $character = auth()->user()->character;
        
        $slot      = $character->inventory->slots->filter(function($slot) use ($request) {
            return $slot->id === (int) $request->slot_id;
        })->first();

        if (is_null($slot)) {
            return redirect()->back()->with('error', 'You don\'t own that item.');
        }

        if ($slot->equipped) {
            return redirect()->back()->with('error', 'Cannot destory equipped item.');
        }

        $name = $slot ->item->name;

        $slot->delete();

        $character->refresh();

        return redirect()->back()->with('success', 'Destroyed ' . $name . '.');
    }

    protected function fetchType(string $type): string {
        $acceptedTypes = [
            'weapon', 'ring', 'shield', 'artifact', 'spell', 'armour'
        ];

        if (in_array($type, $acceptedTypes)) {
            return $type;
        }

        return 'armour';
    }
}
