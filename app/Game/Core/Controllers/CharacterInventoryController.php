<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Events\UpdateCharacterSheetEvent;
use App\Flare\Events\UpdateCharacterInventoryEvent;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Values\MaxDamageForItemValue;
use App\Game\Core\Services\EquipItemService;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Core\Exceptions\EquipItemException;
use App\Game\Core\Requests\ComparisonValidation;

class CharacterInventoryController extends Controller {

    private $equipItemService;

    public function __construct(EquipItemService $equipItemService) {

        $this->equipItemService = $equipItemService;

        $this->middleware('auth');
    }

    public function index(Character $character) {
        $character = auth()->user()->character;
        $inventory = $character->inventory->slots;

        $equipped = $inventory->where('equipped', true)->load([
                'item', 'item.itemAffixes', 'item.artifactProperty'
            ])->transform(function($equippedItem) {
                $equippedItem->item->max_damage = resolve(MaxDamageForItemValue::class)
                                                    ->fetchMaxDamage($equippedItem->item);

                return $equippedItem;
            });

        $chatacterInfo = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return view('game.core.character.inventory', [
            'inventory' => $inventory,
            'equipped'  => $equipped,
            'questItems' => $character->inventory->questItemSlots->load('item', 'item.itemAffixes', 'item.artifactProperty'),
            'characterInfo' => [
                'maxAttack' => $chatacterInfo->buildAttack(),
            ],
        ]);
    }

    public function compare(ComparisonValidation $request) {
        $character   = auth()->user()->character;
        $inventory   = $character->inventory->slots->filter(function($slot) use($request) {
            return $slot->item->type === $request->item_to_equip_type && $slot->equipped;
        });

        $itemToEquip = InventorySlot::find($request->slot_id);
        
        if (is_null($itemToEquip)) {
            return redirect()->back()->with('error', 'Item not found in your inventory.');
        }

        $slotId        = $itemToEquip->id;
        $itemToEquip   = $itemToEquip->item->load(['artifactProperty', 'itemAffixes', 'slot']);
 
        if ($inventory->isEmpty()) {
            return view('game.core.character.equipment-compare', [
                'details'     => [],
                'itemToEquip' => $itemToEquip,
                'type'        => $request->item_to_equip_type,
                'slotId'      => $slotId,
            ]);
        }
       

        return view('game.core.character.equipment-compare', [
            'details'     => $this->equipItemService->setRequest($request)->getItemStats($itemToEquip, $inventory),
            'itemToEquip' => $itemToEquip,
            'type'        => $request->item_to_equip_type,
            'slotId'      => $slotId,
        ]);
    }

    public function equipItem(Request $request) {
        $request->validate([
            'position'   => 'required',
            'slot_id'    => 'required',
            'equip_type' => 'required',
        ]);
        
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
}
