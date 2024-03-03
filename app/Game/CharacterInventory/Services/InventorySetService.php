<?php

namespace App\Game\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Game\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Core\Traits\ResponseBuilder;

class InventorySetService {

    use ResponseBuilder;

    /**
     * @var SetHandsValidation $setHandsValidation
     */
    private SetHandsValidation $setHandsValidation;

    /**
     * @param SetHandsValidation $setHandsValidation
     */
    public function __construct(SetHandsValidation $setHandsValidation) {
        $this->setHandsValidation = $setHandsValidation;
    }

    /**
     * Allows us to add an item to an inventory set.
     *
     * @param InventorySet $inventorySet
     * @param InventorySlot $slot
     */
    public function assignItemToSet(InventorySet $inventorySet, InventorySlot $slot): void
    {
        $inventorySet->slots()->create([
            'inventory_set_id' => $inventorySet->id,
            'item_id'          => $slot->item_id,
        ]);

        $inventorySet = $inventorySet->refresh();

        // Is the inventory set still considered equippable?
        $inventorySet->update([
            'can_be_equipped' => $this->isSetEquippable($inventorySet),
        ]);

        $slot->delete();
    }

    /**
     * Put an item into the characters inventory set.
     *
     * @param InventorySet $set
     * @param Item $item
     */
    public function putItemIntoSet(InventorySet $set, Item $item) {
        $set->slots()->create([
            'inventory_set_id' => $set->id,
            'item_id'          => $item->id
        ]);

        $set = $set->refresh();

        // Is the inventory set still considered equippable?
        $set->update([
            'can_be_equipped' => $this->isSetEquippable($set),
        ]);
    }

    /**
     * Allows us to remove an item from the set.
     *
     * Returns a response object.
     *
     * @param InventorySet $inventorySet
     * @param Item $item
     * @param bool $ignoreInventoryLimit
     * @return array
     */
    public function removeItemFromInventorySet(InventorySet $inventorySet, Item $item, bool $ignoreInventoryLimit = false): array {
        $slotWithItem = $inventorySet->slots->filter(function($slot) use ($item) {
            return $slot->item_id === $item->id;
        })->first();

        $character = $inventorySet->character;

        if ($character->isInventoryFull() && !$ignoreInventoryLimit) {
            return $this->errorResult('Not enough inventory space to put this item back into your inventory.');
        }

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $slotWithItem->item_id,
        ]);

        $slotWithItem->delete();

        $inventorySet = $inventorySet->refresh();

        $inventorySet->update([
            'can_be_equipped' => $this->isSetEquippable($inventorySet),
        ]);

        return $this->successResult([
            'message' => 'Removed ' . $item->affix_name . ' from Set.',
        ]);
    }

    /**
     * Equips an inventory set.
     *
     * Removes the existing equipped items.
     *
     * Return s a refreshed character.
     *
     * @param Character $character
     * @param InventorySet $inventorySet
     * @return Character
     */
    public function equipInventorySet(Character $character, InventorySet $inventorySet): Character {
        $equippedInventorySet = $character->inventorySets()->where('is_equipped', true)->first();

        if (!is_null($equippedInventorySet)) {
            $equippedInventorySet->slots()->update(['equipped' => false]);
            $equippedInventorySet->update(['is_equipped' => false]);
        } else {
            $character->inventory->slots()->where('equipped', true)->update(['equipped' => false]);
        }

        $data = [];

        $armourPositions = ['body','leggings','feet','sleeves','sleeves','helmet','gloves'];

        foreach ($inventorySet->slots as $slot) {
            if ($slot->item->type === 'weapon') {
                $data = $this->setPositionEquipData($slot, $data, 'left-hand', 'right-hand');
            }

            if ($slot->item->type === 'shield') {
                $data = $this->setPositionEquipData($slot, $data, 'left-hand', 'right-hand');
            }

            if (in_array($slot->item->type, ['bow', 'hammer', 'stave'])) {
                $data[$slot->id] = [
                    'item_id' => $slot->item->id,
                    'equipped' => true,
                    'position' => 'left-hand',
                ];
            }

            if ($slot->item->type === 'ring') {
                $data = $this->setPositionEquipData($slot, $data, 'ring-one', 'ring-two');
            }

            if ($slot->item->type === 'spell-damage' || $slot->item->type === 'spell-healing') {
                $data = $this->setPositionEquipData($slot, $data, 'spell-one', 'spell-two');
            }

            if ($slot->item->type === 'trinket') {
                $data = $this->setPositionEquipData($slot, $data, 'trinket-one', 'trinket-two');
            }

            if (in_array($slot->item->default_position, $armourPositions)) {
                $data = $this->setArmourEquipData($slot, $data, $slot->item->default_position);
            }
        }

        foreach ($data as $slotId => $data) {
            $inventorySet->slots()->find($slotId)->update($data);
        }

        $inventorySet->update(['is_equipped' => true]);

        return $character->refresh();
    }

    /**
     * Can un equip a set.
     *
     * @param InventorySet $inventorySet
     */
    public function unEquipInventorySet(InventorySet $inventorySet): void {
        $inventorySet->slots()->update(['equipped' => false]);
        $inventorySet->update(['is_equipped' => false]);
    }

    /**
     * Checks to see if the set is equippable.
     *
     * @param InventorySet $inventorySet
     * @return bool
     */
    public function isSetEquippable(InventorySet $inventorySet): bool {

        // Bail early as our hands are invalid.
        if (!$this->setHandsValidation->isInventorySetHandPositionsValid($inventorySet)) {
            return false;
        }

        $validArmour = ['body','leggings','feet','sleeves','helmet','gloves'];

        foreach ($validArmour as $armourType) {
            // If any of the armour we have in the set doesn't match the count of 1.
            if (!$this->hasArmour($inventorySet, $armourType)) {
                return false;
            }
        }

        // Bail if we have more than two trinkets
        if (!$this->hasTrinkets($inventorySet)) {
            return false;
        }

        // Bail if we have more than two rings.
        if (!$this->hasRings($inventorySet)) {
            return false;
        }

        // Bail if we have more than two spells of either type.
        if (!$this->hasSpells($inventorySet)) {
            return false;
        }

        // Bail if we have more than two artifacts.
        if (!$this->hasArtifacts($inventorySet)) {
            return false;
        }

        // Bail if we have more than one unique.
        if ($this->hasMoreThanOneUnique($inventorySet)) {
            return false;
        }

        // Assume that this set is equippable.
        return true;
    }

    /**
     * Is the type of armour being passed in a count of 1?
     *
     * If you have more than one piece of armour it's a no.
     *
     * @param InventorySet $inventorySet
     * @param string $type
     * @return bool
     */
    protected function hasArmour(InventorySet $inventorySet, string $type) : bool {
        $items = collect($inventorySet->slots->filter(function($slot) use ($type) {
            return $slot->item->type === $type;
        })->all());

        if ($items->count() > 1) {
            return false;
        }

        return true;
    }

    /**
     * Do you only have a max of 2 rings or less?
     *
     * @param InventorySet $inventorySet
     * @return bool
     */
    protected function hasRings(InventorySet $inventorySet): bool {
        $rings = collect($inventorySet->slots->filter(function($slot) {
            return $slot->item->type === 'ring';
        }));

        if ($rings->count() > 2) {
            return false;
        }

        return true;
    }

    /**
     * Do you only have two trinkets?
     *
     * @param InventorySet $inventorySet
     * @return bool
     */
    protected function hasTrinkets(InventorySet $inventorySet): bool {
        $trinkets = collect($inventorySet->slots->filter(function($slot) {
            return $slot->item->type === 'trinket';
        }));

        if ($trinkets->count() > 1) {
            return false;
        }

        return true;
    }

    /**
     * Do you only have a max of 2 artifacts.
     *
     * @param InventorySet $inventorySet
     * @return bool
     */
    public function hasArtifacts(InventorySet $inventorySet): bool {
        $artifacts = collect($inventorySet->slots->filter(function($slot) {
            return $slot->item->type === 'artifact';
        }));

        if ($artifacts->count() > 1) {
            return false;
        }

        return true;
    }

    /**
     * Do you have spells?
     *
     * Valid: 1 Heal, 1 Damage or 2 Heal no Damage or 2 Damage no Heal.
     *
     * @param InventorySet $inventorySet
     * @return bool
     */
    protected function hasSpells(InventorySet $inventorySet) {
        $healingSpells = collect($inventorySet->slots->filter(function($slot) {
            return $slot->item->type === 'spell-healing';
        }));

        $damageSpells = collect($inventorySet->slots->filter(function($slot) {
            return $slot->item->type === 'spell-damage';
        }));

        if ($damageSpells->count() > 2) {
            return false;
        }

        if ($healingSpells->count() > 2) {
            return false;
        }

        if ($healingSpells->count() > 1 && $damageSpells->count() >= 1) {
            return false;
        }

        if ($healingSpells->count() >= 1 && $damageSpells->count() > 1) {
            return false;
        }

        return true;
    }

    /**
     * Do we have more than one unique in the set?
     *
     * @param InventorySet $inventorySet
     * @return bool
     */
    protected function hasMoreThanOneUnique(InventorySet $inventorySet): bool {
        return $inventorySet->slots->filter(function($slot) {
            if (!is_null($slot->item->itemPrefix)) {
                if ($slot->item->itemPrefix->randomly_generated) {
                    return $slot;
                }
            }

            if (!is_null($slot->item->itemSuffix)) {
                if ($slot->item->itemSuffix->randomly_generated) {
                    return $slot;
                }
            }
        })->count() > 1;
    }

    /**
     * Set the position of equipment, except armour.
     *
     * @param SetSlot $slot
     * @param array $data
     * @param string $defaultPosition
     * @param string $oppositePosition
     * @return array
     */
    protected function setPositionEquipData(SetSlot $slot, array $data, string $defaultPosition, string $oppositePosition): array {
        $hasHand = collect($data)->search(function($item) use ($defaultPosition) {
            return $item['position'] === $defaultPosition;
        });

        if ($hasHand === false) {
            $data[$slot->id] = [
                'item_id'  => $slot->item->id,
                'equipped' => true,
                'position' => $defaultPosition,
            ];
        } else {
            $data[$slot->id] = [
                'item_id'  => $slot->item->id,
                'equipped' => true,
                'position' => $oppositePosition,
            ];
        }

        return $data;
    }

    /**
     * Set the position of the armour.
     *
     * @param SetSlot $slot
     * @param array $data
     * @param string $position
     * @return array
     */
    protected function setArmourEquipData(SetSlot $slot, array $data, string $position): array {
        $data[$slot->id] = [
            'item_id'  => $slot->item->id,
            'equipped' => true,
            'position' => $position,
        ];

        return $data;
    }
}
