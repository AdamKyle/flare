<?php

namespace App\Game\Character\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use Illuminate\Database\Eloquent\Collection;

trait FetchEquipped
{
    /**
     * Fetches the equipped items from player.
     */
    public function fetchEquipped(Character $character): ?Collection
    {
        $inventory = $this->getCharacterInventory($character);

        // In rare cases a character was not deleted properly. As a result we want to make sure to mark them as deleted
        // if this happens.
        if (is_null($inventory)) {
            if (! $character->user->will_be_deleted) {
                $character->user()->update([
                    'will_be_deleted' => true,
                ]);
            }

            return null;
        }

        $slots = $this->fetchEquippedInventorySlots($inventory);

        if ($slots->isNotEmpty()) {
            return $slots;
        }

        $inventorySet = $this->fetchEquippedInventorySet($character);

        if (! is_null($inventorySet)) {
            return $this->fetchEquippedSetSlots($inventorySet);
        }

        return null;
    }

    /**
     * Fetch the inventory for the character, preferring loaded relations.
     */
    private function getCharacterInventory(Character $character): ?Inventory
    {
        if ($character->relationLoaded('inventory')) {
            return $character->inventory;
        }

        return Inventory::where('character_id', $character->id)->first();
    }

    /**
     * Fetch equipped inventory slots, preferring loaded relations when item relations are also loaded.
     */
    private function fetchEquippedInventorySlots(Inventory $inventory): Collection
    {
        if ($inventory->relationLoaded('slots')) {
            $slots = $inventory->slots->where('equipped', true);

            if ($slots->isNotEmpty() && $this->inventorySlotHasLoadedItemRelations($slots->first())) {
                return $slots;
            }
        }

        return InventorySlot::where('inventory_id', $inventory->id)
            ->where('equipped', true)
            ->with('item', 'item.itemSuffix', 'item.itemPrefix', 'item.appliedHolyStacks')
            ->get();
    }

    /**
     * Fetch the equipped inventory set for the character, preferring loaded relations.
     */
    private function fetchEquippedInventorySet(Character $character): ?InventorySet
    {
        if ($character->relationLoaded('inventorySets')) {
            $inventorySet = $character->inventorySets->firstWhere('is_equipped', true);

            if (! is_null($inventorySet)) {
                return $inventorySet;
            }
        }

        return InventorySet::where('character_id', $character->id)
            ->where('is_equipped', true)
            ->first();
    }

    /**
     * Fetch equipped set slots, preferring loaded relations when item relations are also loaded.
     */
    private function fetchEquippedSetSlots(InventorySet $inventorySet): Collection
    {
        if ($inventorySet->relationLoaded('slots')) {
            $slots = $inventorySet->slots;

            if ($slots->isEmpty()) {
                return $slots;
            }

            if ($this->setSlotHasLoadedItemRelations($slots->first())) {
                return $slots;
            }
        }

        return SetSlot::where('inventory_set_id', $inventorySet->id)
            ->with('item', 'item.itemSuffix', 'item.itemPrefix', 'item.appliedHolyStacks')
            ->get();
    }

    /**
     * Determine if an inventory slot has all required item relations loaded.
     */
    private function inventorySlotHasLoadedItemRelations(InventorySlot $slot): bool
    {
        return match (true) {
            ! $slot->relationLoaded('item') => false,
            is_null($slot->item) => true,
            ! $slot->item->relationLoaded('itemSuffix') => false,
            ! $slot->item->relationLoaded('itemPrefix') => false,
            ! $slot->item->relationLoaded('appliedHolyStacks') => false,
            default => true,
        };
    }

    /**
     * Determine if a set slot has all required item relations loaded.
     */
    private function setSlotHasLoadedItemRelations(SetSlot $slot): bool
    {
        return match (true) {
            ! $slot->relationLoaded('item') => false,
            is_null($slot->item) => true,
            ! $slot->item->relationLoaded('itemSuffix') => false,
            ! $slot->item->relationLoaded('itemPrefix') => false,
            ! $slot->item->relationLoaded('appliedHolyStacks') => false,
            default => true,
        };
    }
}
