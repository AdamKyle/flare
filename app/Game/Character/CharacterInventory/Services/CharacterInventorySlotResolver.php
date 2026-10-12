<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;

class CharacterInventorySlotResolver
{
    /**
     * Resolve the exact owned Inventory Set slot containing an Item.
     *
     * @param Character $character
     * @param Item $item
     * @param int $setSlotId
     * @return SetSlot|null
     */
    public function setSlot(Character $character, Item $item, int $setSlotId): ?SetSlot
    {
        return SetSlot::where('id', $setSlotId)
            ->where('item_id', $item->id)
            ->whereHas('inventorySet', fn ($query) => $query->where('character_id', $character->id))
            ->first();
    }

    /**
     * Resolve an owned normal Inventory or Inventory Set slot.
     *
     * @param Character $character
     * @param int|Item $slotIdOrItem
     * @return InventorySlot|SetSlot|null
     */
    public function itemSlot(Character $character, int|Item $slotIdOrItem): InventorySlot|SetSlot|null
    {
        $slot = Inventory::where('character_id', $character->id)->first()?->slots()
            ->when($slotIdOrItem instanceof Item, fn ($query) => $query->where('item_id', $slotIdOrItem->id))
            ->when(! ($slotIdOrItem instanceof Item), fn ($query) => $query->where('id', $slotIdOrItem))
            ->first();

        if (! is_null($slot)) {
            return $slot;
        }

        return SetSlot::query()
            ->join('inventory_sets', 'inventory_sets.id', '=', 'set_slots.inventory_set_id')
            ->where('inventory_sets.character_id', $character->id)
            ->when($slotIdOrItem instanceof Item, fn ($query) => $query->where('set_slots.item_id', $slotIdOrItem->id))
            ->when(! ($slotIdOrItem instanceof Item), fn ($query) => $query->where('set_slots.id', $slotIdOrItem))
            ->select('set_slots.*')
            ->first();
    }
}
