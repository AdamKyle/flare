<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Services\Status\BatchCraftingDestinationResolver;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;

class CraftAndEnchantAmountPreviewService
{
    /**
     * @param  CraftingService  $craftingService
     * @param  EnchantingService  $enchantingService
     * @param  BatchCraftingDestinationResolver  $destinationResolver
     * @param  BatchCraftingSetService  $batchCraftingSetService
     */
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly EnchantingService $enchantingService,
        private readonly BatchCraftingDestinationResolver $destinationResolver,
        private readonly BatchCraftingSetService $batchCraftingSetService,
    ) {}

    /**
     * Build the Craft and Enchant Amount preview payload for the validated Batch Crafting request.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array The lean Craft and Enchant Amount preview payload.
     */
    public function build(Character $character, array $validated): array
    {
        $progress = $validated['progress'];
        $item = $this->craftingService->findCraftableItemForAutomation($character, $progress['specific_item_id']);

        if (is_null($item)) {
            return $this->buildUnavailableItemPreview($character, $progress);
        }

        $disposition = BatchCraftingDisposition::from($validated['disposition']);
        $affixes = $this->enchantingService->resolveBatchAffixes($character, $progress['prefix_id'] ?? null, $progress['suffix_id'] ?? null);
        $blockers = [];

        if (! is_null($affixes['error'])) {
            $blockers[] = 'The selected enchantment is no longer available.';
        }

        $craftingCost = $this->craftingService->getItemCostForAutomation($character, $item);
        $enchantingCost = $this->resolveEnchantingCost($character, $item, $affixes);
        $unitCost = $craftingCost + $enchantingCost;
        $requestedAmount = $progress['craft_amount'];
        $availableGold = $character->gold;
        $totalCost = $unitCost * $requestedAmount;
        $canAfford = $availableGold >= $totalCost;

        $destination = null;
        $canFit = true;

        if ($disposition === BatchCraftingDisposition::KEEP) {
            $this->prepareDestination($character, $progress);

            $destination = $this->destinationResolver->resolve($character, $disposition, $progress);
            $canFit = is_null($destination['capacity']) || $destination['capacity']['remaining'] >= $requestedAmount;

            if (! $canFit) {
                $blockers[] = 'The selected destination does not have enough remaining space.';
            }
        }

        if (! $canAfford) {
            $blockers[] = 'You do not have enough Gold to craft and enchant this item.';
        }

        return [
            'item_id' => $item->id,
            'item_name' => $item->affix_name ?? $item->name,
            'requested_amount' => $requestedAmount,
            'prefix' => $this->affixFacts($affixes['prefix']),
            'suffix' => $this->affixFacts($affixes['suffix']),
            'crafting_gold_cost_each' => $craftingCost,
            'enchanting_gold_cost_each' => $enchantingCost,
            'total_gold_cost_each' => $unitCost,
            'total_requested_gold_cost' => $totalCost,
            'gold_available' => $availableGold,
            'disposition' => $disposition->value,
            'output_destination' => $progress['output_destination'] ?? null,
            'output_set_id' => $destination['destination_set_id'] ?? null,
            'output_set_name' => $destination['destination_set_name'] ?? null,
            'listing_price' => $progress['listing_price'] ?? null,
            'destination_capacity' => $destination['capacity'] ?? null,
            'blockers' => $blockers,
        ];
    }

    /**
     * Prepare the requested Keep output destination before it is displayed/started.
     *
     * A Crafted Items Set destination is created here if it does not already exist, so its
     * destination facts are immediately real for the caller, without ever creating a Set as a
     * side effect of read-only status display. Amount only supports Inventory and the Crafted
     * Items Set; a normal specified Inventory Set is reserved for Set-based crafting workflows
     * and is rejected by validation before this method is ever reached.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Craft and Enchant Amount progress data.
     */
    private function prepareDestination(Character $character, array $progress): void
    {
        $destination = BatchCraftingOutputDestination::from($progress['output_destination']);

        if ($destination === BatchCraftingOutputDestination::CRAFTED_ITEMS_SET) {
            $this->batchCraftingSetService->getOrCreateForCharacter($character);
        }
    }

    /**
     * Resolve the Gold cost to apply the resolved affixes to the item, when the affixes are valid.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  Item  $item  The item being enchanted.
     * @param  array{prefix: ItemAffix|null, suffix: ItemAffix|null, error: string|null}  $affixes  The resolved affixes.
     * @return int The Gold cost to apply the resolved affixes.
     */
    private function resolveEnchantingCost(Character $character, Item $item, array $affixes): int
    {
        if (! is_null($affixes['error'])) {
            return 0;
        }

        $affixIds = array_values(array_filter([$affixes['prefix']?->id, $affixes['suffix']?->id]));

        return $this->enchantingService->getCostOfEnchantment($character, $affixIds, $item->id);
    }

    /**
     * Build the factual preview facts for one resolved affix.
     *
     * @param  ItemAffix|null  $affix  The resolved affix, when one was selected.
     * @return array{id: int, name: string, cost: int, int_required: int}|null The affix preview facts, or null when unselected.
     */
    private function affixFacts(?ItemAffix $affix): ?array
    {
        if (is_null($affix)) {
            return null;
        }

        return [
            'id' => $affix->id,
            'name' => $affix->name,
            'cost' => $affix->cost,
            'int_required' => $affix->int_required,
        ];
    }

    /**
     * Build the lean preview payload for a requested item that is no longer craftable.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Craft and Enchant Amount progress data.
     * @return array The lean unavailable-item preview payload.
     */
    private function buildUnavailableItemPreview(Character $character, array $progress): array
    {
        return [
            'item_id' => null,
            'item_name' => null,
            'requested_amount' => $progress['craft_amount'],
            'prefix' => null,
            'suffix' => null,
            'crafting_gold_cost_each' => 0,
            'enchanting_gold_cost_each' => 0,
            'total_gold_cost_each' => 0,
            'total_requested_gold_cost' => 0,
            'gold_available' => $character->gold,
            'disposition' => null,
            'output_destination' => $progress['output_destination'] ?? null,
            'output_set_id' => null,
            'output_set_name' => null,
            'listing_price' => $progress['listing_price'] ?? null,
            'destination_capacity' => null,
            'blockers' => ['The selected item is no longer available to craft.'],
        ];
    }
}
