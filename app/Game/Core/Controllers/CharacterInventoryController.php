<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Services\EquipItemService;
use App\Game\Core\Exceptions\EquipItemException;
use App\Game\Core\Requests\ComparisonValidation;
use App\Game\Core\Requests\EquipItemValidation;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Values\ValidEquipPositionsValue;
use Cache;

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

    public function compare(
        ComparisonValidation $request, 
        ValidEquipPositionsValue $validPositions, 
        CharacterInventoryService $characterInventoryService
    ) {

        $itemToEquip = InventorySlot::find($request->slot_id);

        if (is_null($itemToEquip)) {
            return redirect()->back()->with('error', 'Item not found in your inventory.');
        }

        $service = $characterInventoryService->setCharacter(auth()->user()->character)
                                             ->setInventorySlot($itemToEquip)
                                             ->setPositions($validPositions->getPositions($itemToEquip->item))
                                             ->setInventory($request);

        $viewData = [
            'details'     => [],
            'itemToEquip' => $itemToEquip->item,
            'type'        => $service->getType($request, $itemToEquip->item),
            'slotId'      => $itemToEquip->id,
        ];

        if ($service->inventory()->isNotEmpty()) {
            $viewData = [
                'details'      => $this->equipItemService->setRequest($request)->getItemStats($itemToEquip->item, $service->inventory()),
                'itemToEquip'  => $itemToEquip->item,
                'type'         => $service->getType($request, $itemToEquip->item),
                'slotId'       => $itemToEquip->id,
                'slotPosition' => $itemToEquip->position,
            ];
        }
        

        Cache::put(auth()->user()->id . '-compareItemDetails', $viewData, now()->addMinutes(5));

        return redirect()->to(route('game.inventory.compare-items'));
    }

    public function compareItem() {
        if (!Cache::has(auth()->user()->id . '-compareItemDetails')) {
            redirect()->to('/')->with('error', 'Item comparison expired.');
        }

        return view('game.core.character.equipment-compare', Cache::pull(auth()->user()->id . '-compareItemDetails'));
    }

    public function equipItem(EquipItemValidation $request) {
        try {
            $item = $this->equipItemService->setRequest($request)
                                   ->setCharacter(auth()->user()->character)
                                   ->equipItem();

            return redirect()->to(route('game.character.sheet'))->with('success', $item->affix_name . ' Equipped.');

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

        $name = $slot ->item->affix_name;

        $slot->delete();

        $character->refresh();

        return redirect()->back()->with('success', 'Destroyed ' . $name . '.');
    }
}
