<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Requests\BatchCraftingRequest;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Skills\Services\CraftingService;

class CraftAmountPreviewService
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly CharacterInventoryService $characterInventoryService,
    ) {}

    /**
     * Build the Craft Amount preview payload for the validated Batch Crafting request.
     *
     * @param Character $character The character requesting the preview.
     * @param array $validated The validated Batch Crafting request data.
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

        $destinationResult = $this->resolvePreviewDestinationCapacity($character, $disposition, $progress);
        $destinationCapacity = $destinationResult['capacity'];
        $canFit = $this->canFitRequestedAmount($requestedAmount, $destinationCapacity, $destinationResult['error']);
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
            'blockers' => $this->buildPreviewBlockers($canAfford, $canFit, $destinationResult['error']),
        ];
    }

    /**
     * Find the character's craftable item matching the requested Batch Crafting progress.
     *
     * @param Character $character The character requesting the preview.
     * @param array $progress The requested Craft Amount progress data.
     * @return Item|null The matching craftable item, or null when unavailable.
     */
    private function findCraftableItem(Character $character, array $progress): ?Item
    {
        return $this->craftingService->findCraftableItemForAutomation($character, $progress['specific_item_id']);
    }

    /**
     * Build the lean preview payload for a requested item that is no longer craftable.
     *
     * @param Character $character The character requesting the preview.
     * @param array $progress The requested Craft Amount progress data.
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
     * Every destination is resolved to its own real capacity: Inventory resolves the
     * character's Backpack, Crafted Items Set resolves the special Batch Crafting Set, and
     * a specified normal Inventory Set resolves through the empty-at-start Batch Crafting
     * destination contract so a normal Set's capacity is never silently substituted with the
     * Crafted Items Set's capacity.
     *
     * @param Character $character The character requesting the preview.
     * @param string $destinationValue The requested output destination value.
     * @param int|null $outputSetId The requested destination Inventory Set id, when applicable.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, error: string|null} The resolved capacity facts.
     */
    private function resolveDestinationCapacity(Character $character, string $destinationValue, ?int $outputSetId): array
    {
        $destination = BatchCraftingOutputDestination::from($destinationValue);

        if ($destination === BatchCraftingOutputDestination::INVENTORY) {
            $current = $character->getInventoryCount();
            $max = $character->inventory_max;

            return ['capacity' => ['current' => $current, 'max' => $max, 'remaining' => max(0, $max - $current)], 'error' => null];
        }

        if ($destination === BatchCraftingOutputDestination::CRAFTED_ITEMS_SET) {
            $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
            $remaining = $set->remainingSlots();

            return ['capacity' => ['current' => $set->max_slots - $remaining, 'max' => $set->max_slots, 'remaining' => $remaining], 'error' => null];
        }

        return $this->resolveInventorySetCapacity($character, $outputSetId);
    }

    /**
     * Resolve the requested normal Inventory Set destination's real capacity, validating it is empty at start.
     *
     * @param Character $character The character requesting the preview.
     * @param int|null $outputSetId The requested destination Inventory Set id.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, error: string|null} The resolved capacity facts.
     */
    private function resolveInventorySetCapacity(Character $character, ?int $outputSetId): array
    {
        if (is_null($outputSetId)) {
            return ['capacity' => null, 'error' => 'A destination set is required.'];
        }

        $set = $this->characterInventoryService->setCharacter($character)->resolveEmptyBatchCraftingDestinationSet($outputSetId);

        if (is_null($set)) {
            return ['capacity' => null, 'error' => 'The selected destination set must be an empty, unequipped Set you own.'];
        }

        $remaining = $set->remainingSlots();
        $capacity = is_null($set->max_slots) ? null : ['current' => $set->currentSlotCount(), 'max' => $set->max_slots, 'remaining' => $remaining];

        return ['capacity' => $capacity, 'error' => null];
    }

    /**
     * Resolve the destination capacity relevant to the preview when the disposition retains the item.
     *
     * @param Character $character The character requesting the preview.
     * @param BatchCraftingDisposition $disposition The requested crafting disposition.
     * @param array $progress The requested Craft Amount progress data.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, error: string|null} The resolved destination facts.
     */
    private function resolvePreviewDestinationCapacity(Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        if ($disposition !== BatchCraftingDisposition::KEEP) {
            return ['capacity' => null, 'error' => null];
        }

        return $this->resolveDestinationCapacity($character, $progress['output_destination'], $progress['output_set_id'] ?? null);
    }

    /**
     * Determine whether the requested amount fits within the resolved destination capacity.
     *
     * @param int $requestedAmount The requested Craft Amount.
     * @param array{current: int, max: int, remaining: int}|null $destinationCapacity The resolved destination capacity, if any.
     * @param string|null $destinationError The resolved destination blocker, if any.
     * @return bool True when the requested amount fits.
     */
    private function canFitRequestedAmount(int $requestedAmount, ?array $destinationCapacity, ?string $destinationError): bool
    {
        if (! is_null($destinationError)) {
            return false;
        }

        if (is_null($destinationCapacity)) {
            return true;
        }

        return $destinationCapacity['remaining'] >= $requestedAmount;
    }

    /**
     * Calculate the maximum amount the character can request given Gold and destination capacity.
     *
     * @param int $availableGold The character's available Gold.
     * @param int $unitCost The Gold cost per crafted item.
     * @param array{current: int, max: int, remaining: int}|null $destinationCapacity The resolved destination capacity, if any.
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
     * @param bool $canAfford Whether the character can afford the requested amount.
     * @param bool $canFit Whether the requested amount fits the destination capacity.
     * @param string|null $destinationError The resolved destination blocker, if any.
     * @return array<int, string> The blocking messages, empty when nothing blocks the request.
     */
    private function buildPreviewBlockers(bool $canAfford, bool $canFit, ?string $destinationError): array
    {
        $blockers = [];

        if (! $canAfford) {
            $blockers[] = 'You do not have enough Gold to craft this item.';
        }

        if (! is_null($destinationError)) {
            $blockers[] = $destinationError;
        } elseif (! $canFit) {
            $blockers[] = 'The selected destination does not have enough remaining space.';
        }

        return $blockers;
    }
}
