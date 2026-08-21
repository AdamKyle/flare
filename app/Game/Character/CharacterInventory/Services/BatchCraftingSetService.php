<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;

class BatchCraftingSetService
{
    /**
     * Return the character's Crafted Items Set, creating it when it does not yet exist.
     *
     * @param  Character  $character  The character who owns the Crafted Items Set.
     * @return InventorySet The character's Crafted Items Set.
     */
    public function getOrCreateForCharacter(Character $character): InventorySet
    {
        return InventorySet::firstOrCreate([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ], [
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
    }

    /**
     * Return the character's existing Crafted Items Set without creating one.
     *
     * @param  Character  $character  The character who may own a Crafted Items Set.
     * @return InventorySet|null The character's existing Crafted Items Set, or null when none exists.
     */
    public function findBatchCraftingSet(Character $character): ?InventorySet
    {
        return InventorySet::where('character_id', $character->id)
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();
    }

    /**
     * Return the character's remaining Crafted Items Set capacity.
     *
     * @param  Character  $character  The character who owns the Crafted Items Set.
     * @return int The remaining slot capacity.
     */
    public function remainingSlots(Character $character): int
    {
        return $this->getOrCreateForCharacter($character)->remainingSlots();
    }

    /**
     * Determine whether the character's Crafted Items Set can accept the requested amount.
     *
     * @param  Character  $character  The character who owns the Crafted Items Set.
     * @param  int  $amount  The number of additional slots requested.
     * @return bool True when the Crafted Items Set has enough remaining capacity.
     */
    public function canAccept(Character $character, int $amount): bool
    {
        return $this->remainingSlots($character) >= $amount;
    }

    /**
     * Create a Batch Crafting output item directly as a SetSlot in the Crafted Items Set.
     *
     * Never requires or creates an InventorySlot, and never inspects normal inventory
     * capacity. Checks only the Crafted Items Set's own remaining capacity.
     *
     * @param  Character  $character  The character who owns the Crafted Items Set.
     * @param  Item  $item  The crafted item being placed.
     * @param  string|null  $position  The optional slot position label.
     * @return array{success: bool, reason: string|null, set_slot: SetSlot|null} The placement outcome.
     */
    public function createItemInBatchCraftingSet(Character $character, Item $item, ?string $position = null): array
    {
        $set = $this->getOrCreateForCharacter($character);
        $set = InventorySet::whereKey($set->id)
            ->where('character_id', $character->id)
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();

        if (is_null($set) || $set->remainingSlots() < 1) {
            return ['success' => false, 'reason' => 'set_full', 'set_slot' => null];
        }

        $setSlot = $set->slots()->create([
            'inventory_set_id' => $set->id,
            'item_id' => $item->id,
            'position' => $position,
        ]);

        return ['success' => true, 'reason' => null, 'set_slot' => $setSlot];
    }

    /**
     * Resolve a SetSlot the character genuinely owns inside their Crafted Items Set.
     *
     * Proves the slot belongs to this character's Crafted Items Set and still contains
     * the expected item, rather than trusting a persisted slot id as sufficient authority.
     * A purely read-only ownership check, so it never creates a Crafted Items Set as a
     * side effect: a character with no set genuinely owns no slots.
     *
     * @param  Character  $character  The character who owns the Crafted Items Set.
     * @param  int  $setSlotId  The persisted slot id being resolved.
     * @param  int  $expectedItemId  The item id the slot is expected to still contain.
     * @return SetSlot|null The owned slot, or null when it is not a valid owned result.
     */
    public function findOwnedSlot(Character $character, int $setSlotId, int $expectedItemId): ?SetSlot
    {
        $set = $this->findBatchCraftingSet($character);

        if (is_null($set)) {
            return null;
        }

        return SetSlot::where('id', $setSlotId)
            ->where('inventory_set_id', $set->id)
            ->where('item_id', $expectedItemId)
            ->first();
    }

    /**
     * Replace the item held in an owned SetSlot in place, without changing the Crafted Items Set's slot count.
     *
     * Used to swap a retained Keep Best result for a stronger one: since the slot is updated in
     * place rather than deleted and recreated, the replacement never requires a free slot, even
     * when the Crafted Items Set is already at full capacity.
     *
     * @param  Character  $character  The character who owns the Crafted Items Set.
     * @param  int  $setSlotId  The persisted slot id expected to hold the previous item.
     * @param  int  $expectedItemId  The item id the slot is expected to still contain.
     * @param  Item  $newItem  The item to place into the slot.
     * @return array{success: bool, reason: string|null, set_slot: SetSlot|null, displaced_item: Item|null} The replacement outcome.
     */
    public function replaceItemInSlot(Character $character, int $setSlotId, int $expectedItemId, Item $newItem): array
    {
        $slot = $this->findOwnedSlot($character, $setSlotId, $expectedItemId);

        if (is_null($slot)) {
            return ['success' => false, 'reason' => 'slot_not_found', 'set_slot' => null, 'displaced_item' => null];
        }

        $displacedItem = $slot->item;

        $slot->update(['item_id' => $newItem->id]);

        return ['success' => true, 'reason' => null, 'set_slot' => $slot, 'displaced_item' => $displacedItem];
    }
}
