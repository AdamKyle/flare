<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Traits\ResponseBuilder;

class CharacterInventoryEquipmentService
{
    use ResponseBuilder;

    /**
     * @param CharacterInventoryReadService $characterInventoryReadService
     * @param CharacterInventorySetReadService $characterInventorySetReadService
     */
    public function __construct(
        private readonly CharacterInventoryReadService $characterInventoryReadService,
        private readonly CharacterInventorySetReadService $characterInventorySetReadService,
    ) {}

    /**
     * Unequip one owned Inventory slot.
     *
     * @param Character $character
     * @param int $inventorySlotId
     * @return array
     */
    public function unequipItem(Character $character, int $inventorySlotId): array
    {
        if ($character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full. Cannot unequip items. You have no room in your inventory.');
        }

        $slot = $character->inventory->slots->find($inventorySlotId);

        if (is_null($slot)) {
            return $this->errorResult('No item found to be unequipped.');
        }

        $slot->update(['equipped' => false, 'position' => null]);
        $character = $character->refresh();
        $this->updateCharacterAttackDataCache($character);
        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));

        return $this->successResult([
            'message' => 'Unequipped item: '.$slot->item->affix_name,
            'inventory' => $this->inventoryPayload($character),
        ]);
    }

    /**
     * Unequip every owned Inventory slot.
     *
     * @param Character $character
     * @return array
     */
    public function unequipAll(Character $character): array
    {
        if ($character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full. Cannot unequip items. You have no room in your inventory.');
        }

        $character->inventory->slots->each(fn ($slot) => $slot->update(['equipped' => false, 'position' => null]));
        $character = $character->refresh();
        $this->updateCharacterAttackDataCache($character);

        return $this->successResult([
            'message' => 'All items have been unequipped.',
            'inventory' => $this->inventoryPayload($character),
        ]);
    }

    /**
     * Build the inventory payload returned after unequipping.
     *
     * @param Character $character
     * @return array
     */
    private function inventoryPayload(Character $character): array
    {
        return [
            'inventory' => $this->characterInventoryReadService->fetchInventory($character),
            'equipped' => $this->characterInventoryReadService->equipped($character) ?? [],
            'sets' => $this->characterInventorySetReadService->inventorySets($character),
            'set_is_equipped' => false,
            'set_name_equipped' => $this->characterInventorySetReadService->equippedSetName($character),
            'usable_sets' => $this->characterInventorySetReadService->usableSets($character),
        ];
    }

    /**
     * Dispatch a rebuild of cached Character attack data.
     *
     * @param Character $character
     * @return void
     */
    private function updateCharacterAttackDataCache(Character $character): void
    {
        CharacterAttackTypesCacheBuilder::dispatch($character->refresh());
    }
}
