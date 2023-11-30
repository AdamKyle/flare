<?php

namespace App\Game\CharacterInventory\Handlers\EquipBest;


use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Flare\Values\WeaponTypes;
use App\Game\CharacterInventory\Services\EquipItemService;
use App\Game\CharacterInventory\Values\EquippablePositions;
use Illuminate\Support\Collection;

class HandleHands {

    const TWO_HANDED = [
        WeaponTypes::HAMMER,
        WeaponTypes::BOW,
        WeaponTypes::STAVE,
    ];

    private EquipItemService $equipItemService;

    private InventoryItemComparison $inventoryItemComparison;

    private ?Collection $currentlyEquipped;

    public function __construct(EquipItemService $equipItemService, InventoryItemComparison $inventoryItemComparison) {
        $this->equipItemService = $equipItemService;
        $this->inventoryItemComparison = $inventoryItemComparison;
    }

    public function setCurrentlyEquipped(?Collection $currentlyEquipped = null): HandleHands {

        $this->currentlyEquipped = $currentlyEquipped;

        return $this;
    }

    public function handleHands(Character $character, ?InventorySlot $slotToEquip, string $position): Character {

        if (is_null($slotToEquip)) {
            return $character;
        }

        $oppositePosition = EquippablePositions::getOppisitePosition($position);

        if (is_null($oppositePosition)) {

            $this->equipItem($character, $slotToEquip, $position);

            return $character->refresh();
        }

        $slotForPosition  = $this->getSlotForPosition($oppositePosition);

        if (is_null($slotForPosition)) {
            $this->equipItem($character, $slotToEquip, $position);

            return $character->refresh();
        }

        if (in_array($slotForPosition->item->type, self::TWO_HANDED)) {
            if ($this->inventoryItemComparison->compareItems($slotToEquip->item, $slotForPosition->item)) {
                $this->equipItemService->setCharacter($character)->unequipBothHands();

                $this->equipItem($character, $slotToEquip, $position);

                return $character->refresh();
            }
        }

        return $character;
    }

    protected function equipItem(Character $character, InventorySlot $slotToEquip, string $position) {
        $this->equipItemService->setCharacter($character)->setRequest([
            'position'   => $position,
            'slot_id'    => $slotToEquip->id,
            'equip_type' => $slotToEquip->item->type,
        ])->replaceItem();
    }

    protected function getSlotForPosition(string $position): InventorySlot|SetSlot|null {

        if (is_null($this->currentlyEquipped)) {
            return null;
        }

        return $this->currentlyEquipped->where('position', '=', $position)->first();
    }
}
