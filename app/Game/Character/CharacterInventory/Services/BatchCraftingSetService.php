<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;

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

    public function moveInventorySlotIntoBatchCraftingSet(Character $character, InventorySlot $slot): array
    {
        if ($slot->inventory?->character_id !== $character->id) {
            return ['success' => false, 'reason' => 'not_owned', 'set_slot' => null];
        }

        $set = $this->getOrCreateForCharacter($character);

        if (! $this->canAccept($character, 1)) {
            return ['success' => false, 'reason' => 'set_full', 'set_slot' => null];
        }

        $setSlot = $set->slots()->create([
            'inventory_set_id' => $set->id,
            'item_id' => $slot->item_id,
        ]);

        $slot->delete();

        return ['success' => true, 'reason' => null, 'set_slot' => $setSlot];
    }
}
