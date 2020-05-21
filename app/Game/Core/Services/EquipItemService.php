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
use App\Game\Core\Comparison\WeaponComparison;
use App\Game\Core\Exceptions\EquipItemException;
use Illuminate\Database\Eloquent\Collection;

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

    public function equipItem(): Item {

        $characterSlot = $this->character->inventory->slots->filter(function($slot) {
            return $slot->id === (int) $this->request->slot_id && !$slot->equipped;
        })->first();

        if (is_null($characterSlot)) {
            throw new EquipItemException('Could not equip item because you either do not have it, or it is equipped already.');
        }

        $itemForPosition = $this->character->inventory->slots->filter(function($slot) {
            return $slot->position === $this->request->position; 
        })->first();

        if (!is_null($itemForPosition)) {
            $itemForPosition->update([
                'equipped' => false,
                'position' => null,
            ]);
        }

        $characterSlot->update([
            'equipped' => true,
            'position' => $this->request->position,
        ]);

        event(new UpdateTopBarEvent($this->character));

        return $characterSlot->item;
    }

    public function getItemStats(Item $toCompare, Collection $inventorySlots): array {
        switch($this->request->item_to_equip_type) {
            case 'weapon':
                return resolve(WeaponComparison::class)->fetchDetails($toCompare, $inventorySlots);
            default:
                return [];
        }
    }
}
