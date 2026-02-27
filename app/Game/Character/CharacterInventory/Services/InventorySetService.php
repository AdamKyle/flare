<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\NpcActions\LabyrinthOracle\Events\LabyrinthOracleUpdate;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;

class InventorySetService
{
    use ResponseBuilder;

    private SetHandsValidation $setHandsValidation;

    private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler;

    public function __construct(
        SetHandsValidation $setHandsValidation,
        UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler
    ) {
        $this->setHandsValidation = $setHandsValidation;
        $this->updateCharacterAttackTypesHandler = $updateCharacterAttackTypesHandler;
    }

    /**
     * Allows us to add an item to an inventory set.
     */
    public function assignItemToSet(InventorySet $inventorySet, InventorySlot $slot): void
    {
        $inventorySet->slots()->create([
            'inventory_set_id' => $inventorySet->id,
            'item_id' => $slot->item_id,
        ]);

        $inventorySet = $inventorySet->refresh();

        // Is the inventory set still considered equippable?
        $inventorySet->update([
            'can_be_equipped' => $this->isSetEquippable($inventorySet),
        ]);

        $slot->delete();
    }

    public function fetchSetEquippablityDetails(Character $character, InventorySet $inventorySet): array
    {
        if ($inventorySet->character_id !== $character->id) {
            return $this->errorResult('Not allowed to access a set you do not own.');
        }

        $data = $inventorySet->slots()
            ->join('items as items', 'items.id', '=', 'set_slots.item_id')
            ->whereNotNull('set_slots.item_id')
            ->groupBy('items.type')
            ->selectRaw('items.type as type, COUNT(*) as count')
            ->get()
            ->map(static fn (object $row): array => [
                'type' => $row->type,
                'count' => (int) $row->count,
            ])
            ->all();

        return $this->successResult($data);
    }

    /**
     * Put an item into the characters inventory set.
     */
    public function putItemIntoSet(InventorySet $set, Item $item): void
    {
        $set->slots()->create([
            'inventory_set_id' => $set->id,
            'item_id' => $item->id,
        ]);

        $set = $set->refresh();

        // Is the inventory set still considered equippable?
        $set->update([
            'can_be_equipped' => $this->isSetEquippable($set),
        ]);
    }

    /**
     * Move an item to a set.
     */
    public function moveItemToSet(Character $character, int $slotId, int $setId, bool $fireEvents = true, bool $isLast = false): ?array
    {
        $slot = $character->inventory->slots()->find($slotId);
        $inventorySet = $character->inventorySets()->find($setId);

        if (is_null($slot) || is_null($inventorySet)) {

            return $this->errorResult('Either the slot or the inventory set does not exist.');
        }

        $itemName = $slot->item->affix_name;

        $this->assignItemToSet($inventorySet, $slot);

        $character = $character->refresh();

        if ($fireEvents) {
            event(new UpdateCharacterBaseDetailsEvent($character));

            event(new LabyrinthOracleUpdate($character));

            event(new UpdateCharacterInventoryCountEvent($character));

            if (is_null($inventorySet->name)) {
                $index = $character->inventorySets->search(function ($set) use ($setId) {
                    return $set->id === $setId;
                });

                return $this->successResult([
                    'message' => $itemName.' Has been moved to: Set '.$index + 1,
                ]);
            }

            return $this->successResult([
                'message' => $itemName.' Has been moved to: '.$inventorySet->name,
            ]);
        }

        if ($isLast) {

            if (is_null($inventorySet->name)) {
                $index = $character->inventorySets->search(function ($set) use ($setId) {
                    return $set->id === $setId;
                });

                $setName = 'Set '.$index;
            } else {
                $setName = $inventorySet->name;
            }

            event(new UpdateCharacterInventoryCountEvent($character));

            return $this->successResult([
                'message' => $itemName.' Has been moved to: '.$setName,
            ]);
        }

        return null;
    }

    /**
     * Allows us to remove an item from the set.
     *
     * - Must own the inventory set
     * - Inventory set cannot be equipped.
     */
    public function removeItemFromInventorySet(Character $character, int $inventorySetId, int $slotIdToRemove): array
    {

        $inventorySet = $character->inventorySets->find($inventorySetId);

        if (is_null($inventorySet)) {
            return $this->errorResult('Not allowed to do that.');
        }

        if ($inventorySet->is_equipped) {
            return $this->errorResult('You cannot move an equipped item into your inventory from this set. Unequip the set first.');
        }

        $slot = $inventorySet->slots->find($slotIdToRemove);

        if (is_null($slot)) {
            return $this->errorResult('Item does not exist in this set.');
        }

        $item = $slot->item;

        if (! $this->putItemFromInventorySetBackIntoCharacterInventory($character, $inventorySet, $item)) {
            return $this->errorResult('Not enough inventory space to put this item back into your inventory.');
        }

        if (! is_null($inventorySet->name)) {
            $setName = $inventorySet->name;
        } else {
            $index = $character->inventorySets->search(function ($set) use ($inventorySetId) {
                return $set->id === $inventorySetId;
            });

            $setName = 'Set '.$index + 1;
        }

        event(new UpdateCharacterBaseDetailsEvent($character));

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Removed '.$item->affix_name.' from '.$setName.' and placed back into your inventory.',
        ]);
    }

    /**
     * Empty a characters set.
     */
    public function emptySet(Character $character, InventorySet $inventorySet): array
    {
        if ($character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full. Cannot remove items from set.');
        }

        if ($character->id !== $inventorySet->character_id) {
            return $this->errorResult('Cannot do that.');
        }

        $originalInventorySetCount = $inventorySet->slots->count();
        $itemsRemoved = 0;

        // Only grab the amount of items your inventory can hold.
        foreach ($inventorySet->slots as $slot) {
            if ($this->putItemFromInventorySetBackIntoCharacterInventory($character, $inventorySet, $slot->item)) {
                $itemsRemoved += 1;

                continue;
            }

            break;
        }

        $setIndex = $character->inventorySets->search(function ($set) use ($inventorySet) {
            return $set->id === $inventorySet->id;
        });

        if (is_null($inventorySet->name)) {
            $setName = 'Set '.$setIndex + 1;
        } else {
            $setName = $inventorySet->name;
        }

        $character = $character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Removed '.$itemsRemoved.' of '.$originalInventorySetCount.' items from '.$setName.'. If all items were not moved over, it is because your inventory became full.',
        ]);
    }

    public function putItemFromInventorySetBackIntoCharacterInventory(Character $character, InventorySet $inventorySet, Item $item): bool
    {
        if ($character->isInventoryFull()) {
            return false;
        }

        $slotWithItem = $inventorySet->slots->filter(function ($slot) use ($item) {
            return $slot->item_id === $item->id;
        })->first();

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $slotWithItem->item_id,
        ]);

        $slotWithItem->delete();

        $inventorySet = $inventorySet->refresh();

        $inventorySet->update([
            'can_be_equipped' => $this->isSetEquippable($inventorySet),
        ]);

        return true;
    }

    /**
     * Equips an inventory set.
     *
     * Removes the existing equipped items.
     *
     * Return s a refreshed character.
     */
    public function equipInventorySet(Character $character, InventorySet $inventorySet): Character
    {
        $equippedInventorySet = $character->inventorySets()->where('is_equipped', true)->first();

        if (! is_null($equippedInventorySet)) {
            $equippedInventorySet->slots()->update(['equipped' => false]);
            $equippedInventorySet->update(['is_equipped' => false]);
        } else {
            $character->inventory->slots()->where('equipped', true)->update(['equipped' => false]);
        }

        $data = [];

        $armourPositions = ['body', 'leggings', 'feet', 'sleeves', 'sleeves', 'helmet', 'gloves'];

        foreach ($inventorySet->slots as $slot) {
            if (in_array($slot->item->type, ItemType::validWeapons())) {
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

        foreach ($data as $slotId => $slotData) {
            $inventorySet->slots()->find($slotId)->update($slotData);
        }

        $inventorySet->update(['is_equipped' => true]);

        return $character->refresh();
    }

    /**
     * Can un equip a set.
     */
    public function unEquipInventorySet(InventorySet $inventorySet): void
    {
        $inventorySet->slots()->update(['equipped' => false]);
        $inventorySet->update(['is_equipped' => false]);
    }

    /**
     * Checks to see if the set is equippable.
     */
    public function isSetEquippable(InventorySet $inventorySet): bool
    {

        if ($inventorySet->slots->isEmpty()) {
            return true;
        }

        // Bail early as our hands are invalid.
        if (! $this->setHandsValidation->isInventorySetHandPositionsValid($inventorySet)) {
            return false;
        }

        $validArmour = ['body', 'leggings', 'feet', 'sleeves', 'helmet', 'gloves'];

        foreach ($validArmour as $armourType) {
            // If any of the armour we have in the set doesn't match the count of 1.
            if (! $this->hasArmour($inventorySet, $armourType)) {
                return false;
            }
        }

        // Bail if we have more than two trinkets
        if (! $this->hasTrinkets($inventorySet)) {
            return false;
        }

        // Bail if we have more than two rings.
        if (! $this->hasRings($inventorySet)) {
            return false;
        }

        // Bail if we have more than two spells of either type.
        if (! $this->hasSpells($inventorySet)) {
            return false;
        }

        // Bail if we have more than two artifacts.
        if (! $this->hasArtifacts($inventorySet)) {
            return false;
        }

        return $this->containsValidSpecialTypeAmount($inventorySet);
    }

    /**
     * Unequip a character set.
     */
    public function unequipSet(Character $character): array
    {
        $inventorySet = $character->inventorySets()->where('is_equipped', true)->first();
        $inventoryIndex = $character->inventorySets->search(function ($set) {
            return $set->is_equipped;
        });

        $this->unEquipInventorySet($inventorySet);

        $this->updateCharacterAttackDataCache($character);

        $inventoryName = 'Set '.$inventoryIndex + 1;

        if (! is_null($inventorySet->name)) {
            $inventoryName = $inventorySet->name;
        }

        return $this->successResult([
            'message' => 'Unequipped '.$inventoryName.'.',
        ]);
    }

    /**
     * Equip set.
     */
    public function equipSet(Character $character, InventorySet $inventorySet): array
    {
        if (! $inventorySet->can_be_equipped) {
            return $this->errorResult('Set cannot be equipped. It violates the set rules.');
        }

        if ($inventorySet->character_id !== $character->id) {
            return $this->errorResult('Cannot do that.');
        }

        $this->equipInventorySet($character, $inventorySet);

        $character->refresh();

        $setIndex = $character->inventorySets->search(function ($set) {
            return $set->is_equipped;
        });

        $character = $character->refresh();

        $this->updateCharacterAttackDataCache($character);

        $inventoryName = 'Set '.$setIndex + 1;
        $set = $inventorySet->refresh();

        if (! is_null($set->name)) {
            $inventoryName = $set->name;
        }

        return $this->successResult([
            'message' => $inventoryName.' is now equipped',
        ]);
    }

    /**
     * Updates the character stats.
     *
     * @throws Exception|InvalidArgumentException
     */
    protected function updateCharacterAttackDataCache(Character $character): void
    {
        $this->updateCharacterAttackTypesHandler->updateCache($character);
    }

    /**
     * Is the type of armour being passed in a count of 1?
     *
     * If you have more than one piece of armour it's a no.
     */
    protected function hasArmour(InventorySet $inventorySet, string $type): bool
    {
        $items = collect($inventorySet->slots->filter(function ($slot) use ($type) {
            return $slot->item->type === $type;
        })->all());

        if ($items->count() > 1) {
            return false;
        }

        return true;
    }

    /**
     * Do you only have a max of 2 rings or less?
     */
    protected function hasRings(InventorySet $inventorySet): bool
    {
        $rings = collect($inventorySet->slots->filter(function ($slot) {
            return $slot->item->type === 'ring';
        }));

        if ($rings->count() > 2) {
            return false;
        }

        return true;
    }

    /**
     * Do you only have two trinkets?
     */
    protected function hasTrinkets(InventorySet $inventorySet): bool
    {
        $trinkets = collect($inventorySet->slots->filter(function ($slot) {
            return $slot->item->type === 'trinket';
        }));

        if ($trinkets->count() > 1) {
            return false;
        }

        return true;
    }

    /**
     * Do you only have a max of 2 artifacts.
     */
    public function hasArtifacts(InventorySet $inventorySet): bool
    {
        $artifacts = collect($inventorySet->slots->filter(function ($slot) {
            return $slot->item->type === 'artifact';
        }));

        if ($artifacts->count() > 1) {
            return false;
        }

        return true;
    }

    /**
     * Rename inventory set.
     *
     * - name cannot match any other set name.
     */
    public function renameInventorySet(Character $character, $setId, string $setName): array
    {
        $inventorySet = $character->inventorySets->firstWhere('id', $setId);

        if (is_null($inventorySet)) {

            return $this->errorResult('Set does not exist.');
        }

        if ($character->inventorySets->where('name', $setName)->isNotEmpty()) {
            return $this->errorResult('You already have a set with this name. Pick something else.');
        }

        $inventorySet->update([
            'name' => $setName,
        ]);

        return $this->successResult([
            'message' => 'Renamed set to: '.$setName,
        ]);
    }

    /**
     * Take what ever is equipped and save it to a set. Equip that set.
     *
     * - Set must be empty to save equipped to it.
     */
    public function saveEquippedItemsToSet(Character $character, int $setId): array
    {
        $currentlyEquipped = $character->inventory->slots->filter(function ($slot) {
            return $slot->equipped;
        });

        $inventorySet = $character->inventorySets()->find($setId);

        if ($inventorySet->slots->isNotEmpty()) {
            return $this->errorResult('Set must be empty.');
        }

        foreach ($currentlyEquipped as $equipped) {
            $inventorySet->slots()->create(array_merge(['inventory_set_id' => $inventorySet->id], $equipped->getAttributes()));

            $equipped->delete();
        }

        $inventorySet->update([
            'is_equipped' => true,
        ]);

        $character = $character->refresh();

        $setIndex = $character->inventorySets->search(function ($set) {
            return $set->is_equipped;
        });

        $setName = 'Set '.$setIndex + 1;

        if (! is_null($inventorySet->name)) {
            $setName = $inventorySet->name;
        }

        event(new UpdateCharacterBaseDetailsEvent($character));

        return $this->successResult([
            'message' => $setName.' is now equipped (equipment has been moved to the set).',
        ]);
    }

    /**
     * Do you have spells?
     *
     * Valid: 1 Heal, 1 Damage or 2 Heal no Damage or 2 Damage no Heal.
     *
     * @return bool
     */
    protected function hasSpells(InventorySet $inventorySet)
    {
        $healingSpells = collect($inventorySet->slots->filter(function ($slot) {
            return $slot->item->type === 'spell-healing';
        }));

        $damageSpells = collect($inventorySet->slots->filter(function ($slot) {
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

    protected function containsValidSpecialTypeAmount(InventorySet $inventorySet): bool
    {
        $amountOfUniques = $inventorySet->slots->filter(function ($slot) {
            return ! $slot->item->is_mythic;
        })->filter(function ($slot) {
            return ! $slot->item->is_cosmic;
        })->filter(function ($slot) {
            if (! is_null($slot->item->itemPrefix)) {
                if ($slot->item->itemPrefix->randomly_generated) {
                    return $slot;
                }
            }

            if (! is_null($slot->item->itemSuffix)) {
                if ($slot->item->itemSuffix->randomly_generated) {
                    return $slot;
                }
            }
        })->count();

        if ($amountOfUniques > 1) {
            return false;
        }

        $mythicCount = $inventorySet->slots->where('item.is_mythic', '=', true)->count();

        if ($mythicCount > 1) {
            return false;
        }

        $cosmicCount = $inventorySet->slots->where('item.is_cosmic', '=', true)->count();

        if ($cosmicCount > 1) {
            return false;
        }

        if ($amountOfUniques === 1) {
            return $mythicCount === 0 && $cosmicCount == 0;
        }

        if ($mythicCount === 1) {
            return $amountOfUniques === 0 && $cosmicCount == 0;
        }

        if ($cosmicCount === 1) {
            return $amountOfUniques === 0 && $mythicCount == 0;
        }

        return true;
    }

    /**
     * Set the position of equipment, except armour.
     */
    protected function setPositionEquipData(SetSlot $slot, array $data, string $defaultPosition, string $oppositePosition): array
    {
        $hasHand = collect($data)->search(function ($item) use ($defaultPosition) {
            return $item['position'] === $defaultPosition;
        });

        if ($hasHand === false) {
            $data[$slot->id] = [
                'item_id' => $slot->item->id,
                'equipped' => true,
                'position' => $defaultPosition,
            ];
        } else {
            $data[$slot->id] = [
                'item_id' => $slot->item->id,
                'equipped' => true,
                'position' => $oppositePosition,
            ];
        }

        return $data;
    }

    /**
     * Set the position of the armour.
     */
    protected function setArmourEquipData(SetSlot $slot, array $data, string $position): array
    {
        $data[$slot->id] = [
            'item_id' => $slot->item->id,
            'equipped' => true,
            'position' => $position,
        ];

        return $data;
    }
}
