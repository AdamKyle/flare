<?php

namespace App\Game\CharacterInventory\Handlers\EquipBest;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Game\CharacterInventory\Services\EquipItemService;
use App\Game\CharacterInventory\Values\EquippablePositions;
use Illuminate\Support\Collection;

class HandleTrinketsAndArtifacts {

    private EquipItemService $equipItemService;

    private InventoryItemComparison $inventoryItemComparison;

    private ?Collection $currentlyEquipped;

    public function __construct(EquipItemService $equipItemService,
                                InventoryItemComparison $inventoryItemComparison)
    {
        $this->equipItemService = $equipItemService;
        $this->inventoryItemComparison = $inventoryItemComparison;
    }

    public function setCurrentlyEquipped(?Collection $currentlyEquipped = null): HandleTrinketsAndArtifacts {

        $this->currentlyEquipped = $currentlyEquipped;

        return $this;
    }

    public function handleArtifactOrTrinket(Character $character, string $position, ?InventorySlot $slotToEquip = null): Character {
        if (is_null($slotToEquip)) {
            return $character;
        }

        $trinketSlotEquipped  = $this->getTrinketFromInventory();
        $artifactSlotEquipped = $this->getArtifactFromInventory();

        $itemType = $slotToEquip->item->type;

        if (($itemType === EquippablePositions::TRINKET && is_null($trinketSlotEquipped)) ||
            ($itemType === EquippablePositions::ARTIFACT && is_null($artifactSlotEquipped))) {
            return $this->equipSpecialItem($character, $slotToEquip, $position);
        }

        $equippedSlot = ($itemType === EquippablePositions::TRINKET) ? $trinketSlotEquipped : $artifactSlotEquipped;

        if (!is_null($equippedSlot)) {
            return $this->compareAndPotentiallyReplace($character, $slotToEquip, $equippedSlot, $position);
        }

        return $character;
    }

    protected function compareAndPotentiallyReplace(Character $character, InventorySlot $slotToEquip, InventorySlot|SetSlot $specialSlot, string $position): Character {
        if ($this->inventoryItemComparison->compareItems($slotToEquip->item, $specialSlot->item)) {

            return $this->equipSpecialItem($character, $slotToEquip, $position);
        }

        return $character;
    }

    protected function equipSpecialItem(Character $character, InventorySlot $slotToEquip, string $position): Character {
        $this->equipItemService->setCharacter($character)->setRequest([
            'position'   => $position,
            'slot_id'    => $slotToEquip->id,
            'equip_type' => $slotToEquip->item->type,
        ])->replaceItem();

        $this->replacedSpecialItem = true;

        return $character->refresh();
    }

    protected function getTrinketFromInventory(): InventorySlot|SetSlot|null {
        if (is_null($this->currentlyEquipped)) {
            return null;
        }

        return $this->currentlyEquipped->where('item.type', '=', EquippablePositions::TRINKET)->first();
    }

    protected function getArtifactFromInventory(): InventorySlot|SetSlot|null {
        if (is_null($this->currentlyEquipped)) {
            return null;
        }

        return $this->currentlyEquipped->where('item.type', '=', EquippablePositions::ARTIFACT)->first();
    }
}
