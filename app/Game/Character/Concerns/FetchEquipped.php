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
     *
     * Can return null.
     */
    public function fetchEquipped(Character $character): ?Collection
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        // Somehow the character has no inventory, instead - lets mark them for deletion.
        // These could be characters who deleted their accounts and the deletion failed.
        if (is_null($inventory)) {
            if (! $character->user->will_be_deleted) {
                $character->user()->update([
                    'will_be_deleted' => true,
                ]);
            }

            return null;
        }

        $slots = InventorySlot::where('inventory_id', $inventory->id)->where('equipped', true)->with('item', 'item.itemSuffix', 'item.itemPrefix', 'item.appliedHolyStacks')->get();

        if ($slots->isNotEmpty()) {
            return $slots;
        }

        $inventorySet = InventorySet::where('character_id', $character->id)->where('is_equipped', true)->first();

        if (! is_null($inventorySet)) {
            return SetSlot::where('inventory_set_id', $inventorySet->id)->with('item', 'item.itemSuffix', 'item.itemPrefix', 'item.appliedHolyStacks')->get();
        }

        return null;
    }
}
