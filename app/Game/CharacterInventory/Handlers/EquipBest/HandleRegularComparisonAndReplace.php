<?php

namespace App\Game\CharacterInventory\Handlers\EquipBest;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Game\CharacterInventory\Services\EquipItemService;
use Illuminate\Support\Collection;

class HandleRegularComparisonAndReplace {

    private EquipItemService $equipItemService;

    private InventoryItemComparison $inventoryItemComparison;

    private ?Collection $currentlyEquipped;

    public function __construct(EquipItemService $equipItemService,
                                InventoryItemComparison $inventoryItemComparison)
    {
        $this->equipItemService = $equipItemService;
        $this->inventoryItemComparison = $inventoryItemComparison;
    }

    public function setCurrentlyEquipped(?Collection $currentlyEquipped = null): HandleRegularComparisonAndReplace {

        $this->currentlyEquipped = $currentlyEquipped;

        return $this;
    }

    public function handleRegularComparison(Character $character, ?InventorySlot $slotToEquip, string $position): Character {

        if (is_null($slotToEquip)) {
            return $character;
        }

        $equippedSlot = $this->getEquippedItem($position);

        if (is_null($equippedSlot)) {
            return $this->equipItem($character, $slotToEquip, $position);
        }

        return $this->compareAndPotentiallyReplace($character, $slotToEquip, $equippedSlot, $position);
    }

    protected function compareAndPotentiallyReplace(Character $character, InventorySlot $slotToEquip, InventorySlot|SetSlot $specialSlot, string $position): Character {
        if ($this->inventoryItemComparison->compareItems($slotToEquip->item, $specialSlot->item)) {

            return $this->equipItem($character, $slotToEquip, $position);
        }

        return $character;
    }

    protected function equipItem(Character $character, InventorySlot $slotToEquip, string $position): Character {
        $this->equipItemService->setCharacter($character)->setRequest([
            'position'   => $position,
            'slot_id'    => $slotToEquip->id,
            'equip_type' => $slotToEquip->item->type,
        ])->replaceItem();

        $this->replacedSpecialItem = true;

        return $character->refresh();
    }

    protected function getEquippedItem(string $position): InventorySlot|SetSlot|null {
        if (is_null($this->currentlyEquipped)) {
            return null;
        }

        return $this->currentlyEquipped->where('position', '=', $position)->first();
    }
}
