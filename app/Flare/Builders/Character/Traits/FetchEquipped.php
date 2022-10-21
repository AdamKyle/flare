<?php

namespace App\Flare\Builders\Character\Traits;

use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use \App\Flare\Models\Inventory as InventoryModel;

trait FetchEquipped {

    /**
     * Fetches the equipped items from player.
     *
     * Can return null.
     *
     * @param Character $character
     * @return Collection|null
     */
    public function fetchEquipped(Character $character): Collection|null
    {
        $inventory = InventoryModel::where('character_id', $character->id)->first();
        $slots     = InventorySlot::where('inventory_id', $inventory->id)->where('equipped', true)->with('item', 'item.itemSuffix', 'item.itemPrefix')->get();

        if ($slots->isNotEmpty()) {
            return $slots;
        }

        $inventorySet = InventorySet::where('character_id', $character->id)->where('is_equipped', true)->first();

        if (!is_null($inventorySet)) {
            return SetSlot::where('inventory_set_id', $inventorySet->id)->with('item', 'item.itemSuffix', 'item.itemPrefix')->get();
        }

        return null;
    }
}
