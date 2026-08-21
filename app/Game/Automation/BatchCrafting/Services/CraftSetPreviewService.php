<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;

class CraftSetPreviewService
{
    /**
     * @param  CraftSetPlanService  $craftSetPlanService
     * @param  BatchCraftingSetService  $batchCraftingSetService
     * @param  CharacterInventoryService  $characterInventoryService
     */
    public function __construct(
        private readonly CraftSetPlanService $craftSetPlanService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly CharacterInventoryService $characterInventoryService,
    ) {}

    /**
     * Build the factual Craft Set preview payload for the requested plan and disposition.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Craft Set progress data.
     * @param  string  $disposition  The requested crafting disposition value.
     * @return array The factual Craft Set preview payload.
     */
    public function build(Character $character, array $progress, string $disposition): array
    {
        $plan = $this->craftSetPlanService->resolvePlan($character, $progress['set_positions'] ?? []);
        $blockers = $plan['blockers'];

        $totalCost = $plan['total_cost'];
        $availableGold = $character->gold;
        $canAfford = $availableGold >= $totalCost;

        if (! $canAfford) {
            $blockers[] = 'You do not have enough Gold to craft this set.';
        }

        $dispositionEnum = BatchCraftingDisposition::from($disposition);
        $destinationCapacity = null;
        $canFit = true;

        if ($dispositionEnum === BatchCraftingDisposition::KEEP) {
            $capacityResult = $this->resolveDestinationCapacity($character, $progress, count($plan['queue']));
            $destinationCapacity = $capacityResult['capacity'];
            $canFit = $capacityResult['can_fit'];

            if (! $canFit) {
                $blockers[] = 'The selected destination does not have enough remaining space.';
            }

            if (! is_null($capacityResult['error'])) {
                $blockers[] = $capacityResult['error'];
            }
        }

        return [
            'included_position_count' => count($plan['queue']),
            'total_position_count' => count($this->craftSetPlanService->positions()),
            'positions' => $plan['queue'],
            'total_cost' => $totalCost,
            'available_gold' => $availableGold,
            'can_afford' => $canAfford,
            'output_destination' => $progress['output_destination'] ?? null,
            'destination_capacity' => $destinationCapacity,
            'can_fit' => $canFit,
            'blockers' => $blockers,
        ];
    }

    /**
     * Resolve the current retained-output destination capacity for the requested output destination.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Craft Set progress data.
     * @param  int  $positionCount  The number of actually resolved planned output entries.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, can_fit: bool, error: string|null} The resolved capacity facts.
     */
    private function resolveDestinationCapacity(Character $character, array $progress, int $positionCount): array
    {
        $destination = BatchCraftingOutputDestination::from($progress['output_destination']);

        if ($destination === BatchCraftingOutputDestination::INVENTORY) {
            $current = $character->getInventoryCount();
            $max = $character->inventory_max;
            $remaining = max(0, $max - $current);

            return ['capacity' => ['current' => $current, 'max' => $max, 'remaining' => $remaining], 'can_fit' => $remaining >= $positionCount, 'error' => null];
        }

        if ($destination === BatchCraftingOutputDestination::CRAFTED_ITEMS_SET) {
            $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
            $remaining = $set->remainingSlots();

            return ['capacity' => ['current' => $set->max_slots - $remaining, 'max' => $set->max_slots, 'remaining' => $remaining], 'can_fit' => $remaining >= $positionCount, 'error' => null];
        }

        return $this->resolveInventorySetCapacity($character, $progress, $positionCount);
    }

    /**
     * Resolve the selected normal Inventory Set's destination capacity, validating it is a legal target.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Craft Set progress data.
     * @param  int  $positionCount  The number of planned positions that will be retained.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, can_fit: bool, error: string|null} The resolved capacity facts.
     */
    private function resolveInventorySetCapacity(Character $character, array $progress, int $positionCount): array
    {
        $setId = $progress['output_set_id'] ?? null;

        if (is_null($setId)) {
            return ['capacity' => null, 'can_fit' => false, 'error' => 'A destination set is required.'];
        }

        $set = $this->characterInventoryService->setCharacter($character)->resolveValidTargetInventorySet($setId);

        if (is_null($set)) {
            return ['capacity' => null, 'can_fit' => false, 'error' => 'The selected destination set is not a valid target.'];
        }

        $remaining = $set->remainingSlots();
        $current = $set->currentSlotCount();
        $capacity = is_null($set->max_slots) ? null : ['current' => $current, 'max' => $set->max_slots, 'remaining' => $remaining];

        return ['capacity' => $capacity, 'can_fit' => $remaining >= $positionCount, 'error' => null];
    }
}
