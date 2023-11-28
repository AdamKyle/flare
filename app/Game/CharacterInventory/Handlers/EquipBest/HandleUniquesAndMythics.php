<?php

namespace App\Game\CharacterInventory\Handlers\EquipBest;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Game\CharacterInventory\Services\EquipItemService;
use Illuminate\Support\Collection;

class HandleUniquesAndMythics {

    private EquipItemService $equipItemService;

    private InventoryItemComparison $inventoryItemComparison;

    private ?Collection $currentlyEquipped;

    private bool $replacedSpecialItem = false;

    private string $replacedSpecialPosition = '';

    public function __construct(EquipItemService $equipItemService,
                                InventoryItemComparison $inventoryItemComparison)
    {
        $this->equipItemService = $equipItemService;
        $this->inventoryItemComparison = $inventoryItemComparison;
    }

    public function setCurrentlyEquipped(?Collection $currentlyEquipped = null): HandleUniquesAndMythics {

        $this->currentlyEquipped = $currentlyEquipped;

        return $this;
    }

    public function replacedSpecialItem(): bool {
        return $this->replacedSpecialItem;
    }

    public function getSpecialSlotPosition(): string {
        return $this->replacedSpecialPosition;
    }

    public function handleUniquesOrMythics(Character $character, ?InventorySlot $slotToEquip, string $position): Character {

        if (is_null($slotToEquip)) {
            return $character;
        }

        $mythicEquippedSlot = $this->getMythicFromInventory();
        $uniqueEquippedSlot = $this->getUniqueFromInventory();

        if ($slotToEquip->item->is_unique && !is_null($mythicEquippedSlot)) {
            return $character;
        }

        if ($slotToEquip->item->is_unique && !is_null($uniqueEquippedSlot)) {
            return $this->compareAndPotentiallyReplace(
                $character, $slotToEquip, $uniqueEquippedSlot, $position
            );
        }

        if ($slotToEquip->item->is_mythic && !is_null($uniqueEquippedSlot)) {
            return $this->compareAndPotentiallyReplace(
                $character, $slotToEquip, $uniqueEquippedSlot, $position
            );
        }

        if ($slotToEquip->item->is_mythic && !is_null($mythicEquippedSlot)) {
            return $this->compareAndPotentiallyReplace(
                $character, $slotToEquip, $mythicEquippedSlot, $position
            );
        }

        return $character;
    }

    protected function compareAndPotentiallyReplace(Character $character, InventorySlot $slotToEquip, InventorySlot|SetSlot $specialSlot, string $position): Character {
        if ($this->inventoryItemComparison->compareItems($slotToEquip->item, $specialSlot->item)) {

            if ($specialSlot instanceof SetSlot) {
                $inventory = $specialSlot->inventorySet;
            } else {
                $inventory = $specialSlot->inventory;
            }

            $this->replacedSpecialPosition = $specialSlot->position;

            $this->equipItemService->setCharacter($character)->setRequest([
                'position' => $this->replacedSpecialPosition
            ])->unequipSlot($specialSlot, $inventory);

            $character = $character->refresh();

            $this->equipItemService->setCharacter($character)->setRequest([
                'position'   => $position,
                'slot_id'    => $slotToEquip->id,
                'equip_type' => $slotToEquip->item->type,
            ])->equipItem($character, $slotToEquip, $position);

            $this->replacedSpecialItem = true;

            return $character->refrsh();
        }

        return $character;
    }

    protected function getUniqueFromInventory(): InventorySlot|SetSlot|null {
        if (is_null($this->currentlyEquipped)) {
            return null;
        }

        return $this->currentlyEquipped->where('item.is_unique', true)->first();
    }

    protected function getMythicFromInventory(): InventorySlot|SetSlot|null {
        if (is_null($this->currentlyEquipped)) {
            return null;
        }

        return $this->currentlyEquipped->where('item.is_mythic', true)->first();
    }
}
