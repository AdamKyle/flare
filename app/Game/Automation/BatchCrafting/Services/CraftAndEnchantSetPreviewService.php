<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Services\Status\BatchCraftingDestinationResolver;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use Illuminate\Support\Collection;

class CraftAndEnchantSetPreviewService
{
    /**
     * @param  CraftAndEnchantSetPlanService  $craftAndEnchantSetPlanService
     * @param  CraftingService  $craftingService
     * @param  EnchantingService  $enchantingService
     * @param  BatchCraftingDestinationResolver  $destinationResolver
     * @param  BatchCraftingSetService  $batchCraftingSetService
     * @param  CharacterInventoryService  $characterInventoryService
     */
    public function __construct(
        private readonly CraftAndEnchantSetPlanService $craftAndEnchantSetPlanService,
        private readonly CraftingService $craftingService,
        private readonly EnchantingService $enchantingService,
        private readonly BatchCraftingDestinationResolver $destinationResolver,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly CharacterInventoryService $characterInventoryService,
    ) {}

    /**
     * Build the factual Craft and Enchant Set preview payload for the requested plan and disposition.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Craft and Enchant Set progress data.
     * @param  string  $disposition  The requested crafting disposition value.
     * @return array The factual Craft and Enchant Set preview payload.
     */
    public function build(Character $character, array $progress, string $disposition): array
    {
        $plan = $this->craftAndEnchantSetPlanService->resolvePlan($character, $progress['set_positions'] ?? [], $progress['enchantments'] ?? []);
        $blockers = $plan['blockers'];

        $affixesById = $this->preloadAffixes($plan['queue']);
        $itemsById = $this->preloadItems($plan['queue']);
        $positions = $this->buildPositionFacts($character, $plan['queue'], $affixesById, $itemsById);

        $totalCraftingCost = $plan['total_crafting_cost'];
        $totalEnchantingCost = array_sum(array_column($positions, 'combined_cost')) - $totalCraftingCost;
        $totalCost = $totalCraftingCost + $totalEnchantingCost;
        $availableGold = $character->gold;
        $canAfford = $availableGold >= $totalCost;

        if (! $canAfford) {
            $blockers[] = 'You do not have enough Gold to craft and enchant this set.';
        }

        $dispositionEnum = BatchCraftingDisposition::from($disposition);
        $destination = null;
        $canFit = true;

        if ($dispositionEnum === BatchCraftingDisposition::KEEP) {
            $destinationError = $this->prepareDestination($character, $progress);

            if (! is_null($destinationError)) {
                $blockers[] = $destinationError;
                $canFit = false;
            } else {
                $destination = $this->destinationResolver->resolve($character, $dispositionEnum, $progress);
                $canFit = is_null($destination['capacity']) || $destination['capacity']['remaining'] >= count($plan['queue']);

                if (! $canFit) {
                    $blockers[] = 'The selected destination does not have enough remaining space.';
                }
            }
        }

        return [
            'included_position_count' => count($plan['queue']),
            'positions' => $positions,
            'crafting_gold_total' => $totalCraftingCost,
            'enchanting_gold_total' => $totalEnchantingCost,
            'total_gold_cost' => $totalCost,
            'gold_available' => $availableGold,
            'disposition' => $disposition,
            'output_destination' => $progress['output_destination'] ?? null,
            'output_set_id' => $destination['destination_set_id'] ?? null,
            'output_set_name' => $destination['destination_set_name'] ?? null,
            'listing_price' => $progress['listing_price'] ?? null,
            'destination_capacity' => $destination['capacity'] ?? null,
            'can_fit' => $canFit,
            'blockers' => $blockers,
        ];
    }

    /**
     * Validate and prepare the requested Keep output destination before it is displayed/started.
     *
     * A specified normal Inventory Set must be empty, unequipped, and owned by the character at
     * this point. A Crafted Items Set destination is created here if it does not already exist,
     * so its destination facts are immediately real for the caller, without ever creating a Set
     * as a side effect of read-only status display.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Craft and Enchant Set progress data.
     * @return string|null The destination blocker, or null when the destination is ready.
     */
    private function prepareDestination(Character $character, array $progress): ?string
    {
        $destination = BatchCraftingOutputDestination::from($progress['output_destination']);

        if ($destination === BatchCraftingOutputDestination::CRAFTED_ITEMS_SET) {
            $this->batchCraftingSetService->getOrCreateForCharacter($character);

            return null;
        }

        if ($destination !== BatchCraftingOutputDestination::INVENTORY_SET) {
            return null;
        }

        $outputSetId = $progress['output_set_id'] ?? null;
        $set = is_null($outputSetId)
            ? null
            : $this->characterInventoryService->setCharacter($character)->resolveEmptyBatchCraftingDestinationSet($outputSetId);

        return is_null($set) ? 'The selected destination set must be an empty, unequipped Set you own.' : null;
    }

    /**
     * Preload every affix referenced by the resolved plan queue, in one bounded query.
     *
     * @param  array<int, array>  $queue  The resolved plan queue entries.
     * @return Collection<int, ItemAffix> The preloaded affixes, keyed by id.
     */
    private function preloadAffixes(array $queue): Collection
    {
        $ids = [];

        foreach ($queue as $entry) {
            $ids[] = $entry['prefix_id'];
            $ids[] = $entry['suffix_id'];
        }

        return ItemAffix::whereIn('id', array_values(array_unique(array_filter($ids))))->get()->keyBy('id');
    }

    /**
     * Preload every item referenced by the resolved plan queue, in one bounded query.
     *
     * @param  array<int, array>  $queue  The resolved plan queue entries.
     * @return Collection<int, Item> The preloaded items, keyed by id.
     */
    private function preloadItems(array $queue): Collection
    {
        $ids = array_values(array_unique(array_column($queue, 'item_id')));

        return Item::whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Build the per-position preview facts for the resolved plan queue.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array<int, array>  $queue  The resolved plan queue entries.
     * @param  Collection<int, ItemAffix>  $affixesById  The preloaded affixes, keyed by id.
     * @param  Collection<int, Item>  $itemsById  The preloaded items, keyed by id.
     * @return array<int, array> The per-position preview facts.
     */
    private function buildPositionFacts(Character $character, array $queue, Collection $affixesById, Collection $itemsById): array
    {
        return array_map(function (array $entry) use ($character, $affixesById, $itemsById): array {
            $prefix = is_null($entry['prefix_id']) ? null : $affixesById->get($entry['prefix_id']);
            $suffix = is_null($entry['suffix_id']) ? null : $affixesById->get($entry['suffix_id']);
            $affixIds = array_values(array_filter([$entry['prefix_id'], $entry['suffix_id']]));
            $item = $itemsById->get($entry['item_id']);
            $craftingCost = is_null($item) ? 0 : $this->craftingService->getItemCostForAutomation($character, $item);
            $enchantingCost = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $entry['item_id']);

            return [
                'position' => $entry['position'],
                'item_name' => $entry['item_name'],
                'prefix' => $this->affixFacts($prefix),
                'suffix' => $this->affixFacts($suffix),
                'crafting_cost' => $craftingCost,
                'enchanting_cost' => $enchantingCost,
                'combined_cost' => $craftingCost + $enchantingCost,
            ];
        }, $queue);
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
}
