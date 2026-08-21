<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Requests\BatchCraftingRequest;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Services\CraftingService;

class CraftAmountPreviewService
{
    /**
     * @param  CraftingService  $craftingService
     * @param  BatchCraftingSetService  $batchCraftingSetService
     */
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
    ) {}

    /**
     * Build the Craft Amount preview payload for the validated Batch Crafting request.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array The lean Craft Amount preview payload.
     */
    public function build(Character $character, array $validated): array
    {
        $progress = $validated['progress'];

        $item = $this->findCraftableItem($character, $progress);

        if (is_null($item)) {
            return $this->buildUnavailableItemPreview($character, $progress);
        }

        $requestedAmount = $progress['craft_amount'];
        $disposition = BatchCraftingDisposition::from($validated['disposition']);
        $unitCost = $this->craftingService->getItemCostForAutomation($character, $item);
        $availableGold = $character->gold;
        $totalCost = $unitCost * $requestedAmount;
        $canAfford = $availableGold >= $totalCost;
        $goldAfterPurchase = max(0, $availableGold - $totalCost);

        $destinationCapacity = $this->resolvePreviewDestinationCapacity($character, $disposition, $progress);
        $canFit = $this->canFitRequestedAmount($requestedAmount, $destinationCapacity);
        $maximumRequestAmount = $this->maximumRequestAmount($availableGold, $unitCost, $destinationCapacity);

        return [
            'item' => ['id' => $item->id, 'name' => $item->affix_name ?? $item->name],
            'requested_amount' => $requestedAmount,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'available_gold' => $availableGold,
            'gold_after_purchase' => $goldAfterPurchase,
            'can_afford' => $canAfford,
            'output_destination' => $progress['output_destination'] ?? null,
            'destination_capacity' => $destinationCapacity,
            'can_fit' => $canFit,
            'maximum_request_amount' => $maximumRequestAmount,
            'blockers' => $this->buildPreviewBlockers($canAfford, $canFit),
        ];
    }

    /**
     * Find the character's craftable item matching the requested Batch Crafting progress.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Craft Amount progress data.
     * @return Item|null The matching craftable item, or null when unavailable.
     */
    private function findCraftableItem(Character $character, array $progress): ?Item
    {
        return $this->craftingService->findCraftableItemForAutomation($character, $progress['specific_item_id']);
    }

    /**
     * Build the lean preview payload for a requested item that is no longer craftable.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Craft Amount progress data.
     * @return array The lean unavailable-item preview payload.
     */
    private function buildUnavailableItemPreview(Character $character, array $progress): array
    {
        return [
            'item' => null,
            'requested_amount' => $progress['craft_amount'],
            'unit_cost' => 0,
            'total_cost' => 0,
            'available_gold' => $character->gold,
            'gold_after_purchase' => $character->gold,
            'can_afford' => false,
            'output_destination' => $progress['output_destination'] ?? null,
            'destination_capacity' => null,
            'can_fit' => false,
            'maximum_request_amount' => 0,
            'blockers' => ['The selected item is no longer available to craft.'],
        ];
    }

    /**
     * Resolve the character's current capacity for the requested output destination.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  string  $destinationValue  The requested output destination value.
     * @return array{current: int, max: int, remaining: int} The destination's current, max, and remaining capacity.
     */
    private function resolveDestinationCapacity(Character $character, string $destinationValue): array
    {
        $destination = BatchCraftingOutputDestination::from($destinationValue);

        if ($destination === BatchCraftingOutputDestination::INVENTORY) {
            $current = $character->getInventoryCount();
            $max = $character->inventory_max;

            return ['current' => $current, 'max' => $max, 'remaining' => max(0, $max - $current)];
        }

        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $remaining = $set->remainingSlots();

        return ['current' => $set->max_slots - $remaining, 'max' => $set->max_slots, 'remaining' => $remaining];
    }

    /**
     * Resolve the destination capacity relevant to the preview when the disposition retains the item.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  BatchCraftingDisposition  $disposition  The requested crafting disposition.
     * @param  array  $progress  The requested Craft Amount progress data.
     * @return array{current: int, max: int, remaining: int}|null The destination capacity, or null when not retaining the item.
     */
    private function resolvePreviewDestinationCapacity(Character $character, BatchCraftingDisposition $disposition, array $progress): ?array
    {
        if ($disposition !== BatchCraftingDisposition::KEEP) {
            return null;
        }

        return $this->resolveDestinationCapacity($character, $progress['output_destination']);
    }

    /**
     * Determine whether the requested amount fits within the resolved destination capacity.
     *
     * @param  int  $requestedAmount  The requested Craft Amount.
     * @param  array{current: int, max: int, remaining: int}|null  $destinationCapacity  The resolved destination capacity, if any.
     * @return bool True when the requested amount fits.
     */
    private function canFitRequestedAmount(int $requestedAmount, ?array $destinationCapacity): bool
    {
        if (is_null($destinationCapacity)) {
            return true;
        }

        return $destinationCapacity['remaining'] >= $requestedAmount;
    }

    /**
     * Calculate the maximum amount the character can request given Gold and destination capacity.
     *
     * @param  int  $availableGold  The character's available Gold.
     * @param  int  $unitCost  The Gold cost per crafted item.
     * @param  array{current: int, max: int, remaining: int}|null  $destinationCapacity  The resolved destination capacity, if any.
     * @return int The maximum amount the character can request.
     */
    private function maximumRequestAmount(int $availableGold, int $unitCost, ?array $destinationCapacity): int
    {
        $affordableAmount = BatchCraftingRequest::MAX_CRAFT_AMOUNT;

        if ($unitCost > 0) {
            $affordableAmount = intdiv($availableGold, $unitCost);
        }

        $destinationRemaining = BatchCraftingRequest::MAX_CRAFT_AMOUNT;

        if (! is_null($destinationCapacity)) {
            $destinationRemaining = $destinationCapacity['remaining'];
        }

        return min(
            BatchCraftingRequest::MAX_CRAFT_AMOUNT,
            max(0, $affordableAmount),
            max(0, $destinationRemaining),
        );
    }

    /**
     * Build the blocking messages for the Craft Amount preview.
     *
     * @param  bool  $canAfford  Whether the character can afford the requested amount.
     * @param  bool  $canFit  Whether the requested amount fits the destination capacity.
     * @return array<int, string> The blocking messages, empty when nothing blocks the request.
     */
    private function buildPreviewBlockers(bool $canAfford, bool $canFit): array
    {
        $blockers = [];

        if (! $canAfford) {
            $blockers[] = 'You do not have enough Gold to craft this item.';
        }

        if (! $canFit) {
            $blockers[] = 'The selected destination does not have enough remaining space.';
        }

        return $blockers;
    }
}
