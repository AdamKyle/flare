<?php

namespace App\Game\Core\Services;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Events\UpdateCharacterSheetEvent;
use App\Flare\Events\UpdateCharacterInventoryEvent;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Models\EquippedItem;

class EquipItemService {

    private $request;

    private $character;

    public function setRequest(Request $request): EquipItemService {
        $this->request = $request;

        return $this;
    }

    public function setCharacter(Character $character): EquipItemService {
        $this->character = $character;

        return $this;
    }

    public function equipItem(): JsonResponse {
        $item          = Item::find($this->request->item_id);

        $characterItem = $this->character->inventory->slots->filter(function($slot) use ($item) {
            return $slot->item->id === $item->id;
        })->first();

        if (is_null($characterItem)) {
            return response()->json([
                'message' => 'Cannot equip ' . $item->name . '. You do not currently have this in yor inventory.',
            ], 422);
        }

        $equippedItem = $this->getEquippedItem($characterItem);

        if (!is_null($equippedItem)) {
            return response()->json([
                'message' => 'Cannot equip ' . $characterItem->item->name . ' to the same hand.',
            ], 422);
        } else {
            $equippedItemToSwitch = $this->getEquippedItemFromId($characterItem);

            if (!is_null($equippedItemToSwitch)) {
                return $this->switchItemPosition($equippedItemToSwitch, $characterItem);
            }

            $equippedItemInPosition = $this->getItemForPosition();

            if (!is_null($equippedItemInPosition)) {
                return $this->updateEquipmentSlot($characterItem, $equippedItemInPosition);
            }

            return $this->attachItem($characterItem);
        }
    }

    public function getEquippedItemFromId(InventorySlot $characterItem) {
        return $this->character->equippedItems
                               ->where('item_id', '=', $characterItem->id)
                               ->first();
    }

    public function getItemForPosition() {
        return $this->character->equippedItems
                               ->where('position', '=', $this->request->position)
                               ->first();
    }

    protected function getEquippedItem(InventorySlot $characterItem) {
        return $this->character->equippedItems
                               ->where('position', '=', $this->request->position)
                               ->where('item_id', '=', $characterItem->id)
                               ->first();
    }

    protected function switchItemPosition(EquippedItem $item, InventorySlot $characterItem): JsonResponse {
        $item->update([
            'position' => $this->request->position,
        ]);

        $this->character->refresh();

        event(new UpdateTopBarEvent($this->character));
        event(new UpdateCharacterSheetEvent($this->character));
        event(new UpdateCharacterInventoryEvent($this->character));
        event(new UpdateCharacterAttackEvent($this->character));

        return response()->json([
            'message' => 'Switched: ' . $characterItem->item->name . ' to: ' . str_replace('-', ' ', Str::title($this->request->position)) . '.',
        ], 200);
    }

    protected function attachItem(InventorySlot $characterItem): JsonResponse {
        if ($characterItem->item->type !== $this->request->equip_type) {
            return response()->json([
                'message' => 'Cannot equip ' . $characterItem->item->name . ' as it is not of type: ' . $this->request->equip_type,
            ], 422);
        }

        $this->character->equippedItems()->create([
            'item_id'  => $characterItem->item->id,
            'position' => $this->request->position,
        ]);

        $this->character->refresh();

        event(new UpdateTopBarEvent($this->character));
        event(new UpdateCharacterSheetEvent($this->character));
        event(new UpdateCharacterInventoryEvent($this->character));
        event(new UpdateCharacterAttackEvent($this->character));

        return response()->json([
            'message' => 'Equipped: ' . $characterItem->item->name . ' to: ' . str_replace('-', ' ', Str::title($this->request->position)),
        ], 200);
    }

    protected function updateEquipmentSlot(InventorySlot $characterItem, EquippedItem $equippedItem): JsonResponse {
        if ($characterItem->item->type !== $this->request->equip_type) {
            return response()->json([
                'message' => 'Cannot equip ' . $characterItem->item->name . ' as it is not of type: ' . $this->request->equip_type,
            ], 422);
        }

        $equippedItem->update([
            'item_id' => $characterItem->item->id,
            'position' => $this->request->position,
        ]);

        $this->character->refresh();

        event(new UpdateTopBarEvent($this->character));
        event(new UpdateCharacterSheetEvent($this->character));
        event(new UpdateCharacterInventoryEvent($this->character));
        event(new UpdateCharacterAttackEvent($this->character));

        return response()->json([
            'message' => 'Equipped: ' . $characterItem->item->name . ' to: ' . str_replace('-', ' ', Str::title($this->request->position)),
        ], 200);
    }
}
