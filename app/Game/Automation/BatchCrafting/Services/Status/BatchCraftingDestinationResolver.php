<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;

class BatchCraftingDestinationResolver
{
    public function __construct(
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
    ) {}

    /**
     * Resolve the retained-item destination facts for a batch's disposition and configured output destination.
     *
     * @param Character $character The character the batch belongs to.
     * @param BatchCraftingDisposition $disposition The batch's configured disposition.
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, destination_set_id: int|null, destination_set_name: string|null} The resolved destination facts.
     */
    public function resolve(Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $empty = ['capacity' => null, 'destination_set_id' => null, 'destination_set_name' => null];

        if ($disposition !== BatchCraftingDisposition::KEEP) {
            return $empty;
        }

        $destination = BatchCraftingOutputDestination::from($progress['output_destination']);

        return match ($destination) {
            BatchCraftingOutputDestination::INVENTORY => $this->resolveInventoryDestination($character),
            BatchCraftingOutputDestination::CRAFTED_ITEMS_SET => $this->resolveCraftedItemsSetDestination($character),
            BatchCraftingOutputDestination::INVENTORY_SET => $this->resolveInventorySetDestination($character, $progress),
        };
    }

    /**
     * Resolve the Backpack destination capacity facts for a retained Inventory batch.
     *
     * @param Character $character The character the batch belongs to.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, destination_set_id: int|null, destination_set_name: string|null} The resolved Inventory destination facts.
     */
    private function resolveInventoryDestination(Character $character): array
    {
        $current = $character->getInventoryCount();
        $max = $character->inventory_max;

        return [
            'capacity' => ['current' => $current, 'max' => $max, 'remaining' => max(0, $max - $current)],
            'destination_set_id' => null,
            'destination_set_name' => null,
        ];
    }

    /**
     * Resolve the existing Crafted Items Set destination facts without creating a set.
     *
     * @param Character $character The character the batch belongs to.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, destination_set_id: int|null, destination_set_name: string|null} The resolved Crafted Items Set facts.
     */
    private function resolveCraftedItemsSetDestination(Character $character): array
    {
        $set = $this->batchCraftingSetService->findBatchCraftingSet($character);

        if (is_null($set)) {
            return ['capacity' => null, 'destination_set_id' => null, 'destination_set_name' => null];
        }

        return [
            'capacity' => [
                'current' => $set->currentSlotCount(),
                'max' => $set->max_slots,
                'remaining' => $set->remainingSlots(),
            ],
            'destination_set_id' => $set->id,
            'destination_set_name' => $set->name,
        ];
    }

    /**
     * Resolve the selected normal Inventory Set destination facts.
     *
     * @param Character $character The character the batch belongs to.
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, destination_set_id: int|null, destination_set_name: string|null} The resolved Inventory Set facts.
     */
    private function resolveInventorySetDestination(Character $character, array $progress): array
    {
        $setId = $progress['output_set_id'] ?? null;
        $set = is_null($setId) ? null : $this->characterInventoryService->setCharacter($character)->findOwnedInventorySet($setId);

        if (is_null($set)) {
            return ['capacity' => null, 'destination_set_id' => null, 'destination_set_name' => null];
        }

        $capacity = is_null($set->max_slots) ? null : [
            'current' => $set->currentSlotCount(),
            'max' => $set->max_slots,
            'remaining' => $set->remainingSlots(),
        ];

        return [
            'capacity' => $capacity,
            'destination_set_id' => $set->id,
            'destination_set_name' => $set->name,
        ];
    }
}
