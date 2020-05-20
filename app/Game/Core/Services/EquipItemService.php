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
use App\Game\Core\Exceptions\EquipItemException;

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

    public function isItemBetter(Item $toCompare, Item $equipped): bool {
        $totalDamageForEquipped = $this->getItemDamage($equipped);
        $totalDamageForCompare  = $this->getItemDamage($toCompare);

        if ($totalDamageForCompare > $totalDamageForEquipped) {
            return true;
        }

        return false;
    }

    protected function getItemDamage(Item $item): int {
        $attack = $item->base_damage;


        $artifact = $item->artifactProperty;
        
        if (!is_null($artifact)) {
            $attack += $artifact->base_damage_mod;
        }

        $affixes = $item->itemAffixes;

        if ($affixes->isNotEmpty()) {
            foreach($affixes as $affix) {
                $attack += $affix->base_damage_mod;
            }
        }

        return $attack;
    }
}
