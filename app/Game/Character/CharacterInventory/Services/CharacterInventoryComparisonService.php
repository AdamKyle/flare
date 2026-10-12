<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CharacterInventoryComparisonService
{
    /**
     * Return equipped slots matching the comparison positions.
     *
     * @param Character $character
     * @param InventorySlot|null $inventorySlot
     * @param array $positions
     * @return Collection
     */
    public function comparisonInventory(Character $character, ?InventorySlot $inventorySlot, array $positions): Collection
    {
        $inventory = $character->inventory->slots()
            ->whereIn('position', $positions)
            ->when(! is_null($inventorySlot), fn ($query) => $query->where('id', '!=', $inventorySlot->id))
            ->get();

        if ($inventory->isNotEmpty()) {
            return $inventory;
        }

        $slots = $character->inventorySets()
            ->where('is_equipped', true)
            ->whereHas('slots', fn ($query) => $query->whereIn('position', $positions))
            ->get()
            ->pluck('slots')
            ->flatten();

        return new Collection($slots);
    }

    /**
     * Return the normalized comparison type for an Item.
     *
     * @param Item $item
     * @return string|null
     */
    public function itemType(Item $item): ?string
    {
        $type = $item->type;

        if (in_array($type, ArmourType::allTypes(), true)) {
            return 'armour';
        }

        if (in_array($type, ['spell-damage', 'spell-healing'], true)) {
            return 'spell';
        }

        $acceptedTypes = [...ItemType::validWeapons(), 'ring', 'shield', 'artifact', 'spell', 'trinket', 'alchemy', 'quest'];

        if (in_array($type, $acceptedTypes, true)) {
            return $type;
        }

        Log::error('Unable to normalize inventory item type.', [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'item_type' => $item->type,
        ]);

        return null;
    }
}
