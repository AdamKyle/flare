<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use Illuminate\Support\Facades\DB;

class BatchCraftingSetService
{
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

    public function remainingSlots(Character $character): int
    {
        return $this->getOrCreateForCharacter($character)->remainingSlots();
    }

    public function canAccept(Character $character, int $amount): bool
    {
        return $this->remainingSlots($character) >= $amount;
    }

    /**
     * Create a Batch Crafting output item directly as a SetSlot in the Crafted Items Set.
     *
     * Never requires or creates an InventorySlot, and never inspects normal inventory
     * capacity. Checks only the Crafted Items Set's own remaining capacity.
     */
    public function createItemInBatchCraftingSet(Character $character, Item $item, ?string $position = null): array
    {
        $this->getOrCreateForCharacter($character);

        $result = ['success' => false, 'reason' => 'set_full', 'set_slot' => null];

        DB::transaction(function () use ($character, $item, $position, &$result) {
            $set = InventorySet::where('character_id', $character->id)
                ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
                ->lockForUpdate()
                ->first();

            if ($set->remainingSlots() < 1) {
                return;
            }

            $setSlot = $set->slots()->create([
                'inventory_set_id' => $set->id,
                'item_id' => $item->id,
                'position' => $position,
            ]);

            $result = ['success' => true, 'reason' => null, 'set_slot' => $setSlot];
        });

        return $result;
    }
}
