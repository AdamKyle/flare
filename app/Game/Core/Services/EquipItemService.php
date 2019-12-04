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

        if ($characterItem->equipped) {
            $equippedItem = $this->getEquippedItem($characterItem);

            if (!is_null($equippedItem)) {
                return response()->json([
                    'message' => 'Cannot equip ' . $characterItem->item->name . ' to the same hand.',
                ], 422);
            }

            return $this->switchItemPosition($characterItem);
        }

        return $this->attachItem($characterItem);
    }

    protected function getEquippedItem(InventorySlot $characterItem) {
        return $this->character->equippedItems
                               ->where('type', '=', $this->request->type)
                               ->where('item_id', '=', $characterItem->id)
                               ->first();
    }

    protected function switchItemPosition(InventorySlot $characterItem): JsonResponse {
        $this->character->equippedItems()->create([
            'item_id' => $characterItem->item->id,
            'type'    => $this->request->type,
        ]);

        $this->character->equippedItems
                        ->where('item_id', '=', $characterItem->item->id)
                        ->where('type', '!=', $this->request->type)
                        ->first()
                        ->delete();

        event(new UpdateTopBarEvent($this->character));
        event(new UpdateCharacterSheetEvent($this->character));

        return response()->json([
            'message' => 'Switched: ' . $characterItem->item->name . ' to: ' . str_replace('-', ' ', Str::title($this->request->type)) . '.',
        ], 200);
    }

    protected function attachItem(InventorySlot $characterItem): JsonResponse {
        if ($characterItem->item->type !== $this->request->equip_type) {
            return response()->json([
                'message' => 'Cannot equip ' . $characterItem->item->name . ' as it is not of type: ' . $this->request->equip_type,
            ], 422);
        }

        $this->character->equippedItems()->create([
            'item_id' => $characterItem->item->id,
            'type'    => $this->request->type,
        ]);

        event(new UpdateTopBarEvent($this->character));
        event(new UpdateCharacterSheetEvent($this->character));

        return response()->json([
            'message' => 'Equipped: ' . $characterItem->item->name . ' to: ' . str_replace('-', ' ', Str::title($this->request->type)),
        ], 200);
    }
}
