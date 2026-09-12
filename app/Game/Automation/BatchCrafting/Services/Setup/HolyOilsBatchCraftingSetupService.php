<?php

namespace App\Game\Automation\BatchCrafting\Services\Setup;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\HolyOilsBatchMode;
use App\Game\Automation\BatchCrafting\Enums\HolyOilTargetKind;
use App\Game\Automation\BatchCrafting\Services\HolyOilSelectedItemsPreviewService;
use App\Game\Automation\BatchCrafting\Services\HolyOilSetPreviewService;
use App\Game\Automation\BatchCrafting\Values\HolyOilApplicationPlanEntry;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;

class HolyOilsBatchCraftingSetupService implements BatchCraftingSetupService
{
    public function __construct(
        private readonly HolyOilSelectedItemsPreviewService $holyOilSelectedItemsPreviewService,
        private readonly HolyOilSetPreviewService $holyOilSetPreviewService,
        private readonly CharacterInventoryService $characterInventoryService,
    ) {}

    /**
     * Determine whether this setup service owns preview/start resolution for the given Batch Crafting type.
     *
     * @param BatchCraftingType $type The requested Batch Crafting type.
     * @return bool True when this setup service owns the given type.
     */
    public function supports(BatchCraftingType $type): bool
    {
        return $type === BatchCraftingType::HOLY_OILS;
    }

    /**
     * Build the preview result for the validated request.
     *
     * @param Character $character The character requesting the preview.
     * @param array $validated The validated Batch Crafting request data.
     * @return array The Holy Oils preview payload.
     */
    public function preview(Character $character, array $validated): ?array
    {
        $mode = HolyOilsBatchMode::from($validated['progress']['holy_oils_mode']);

        return match ($mode) {
            HolyOilsBatchMode::SELECTED_ITEMS => $this->holyOilSelectedItemsPreviewService->build($character, $validated),
            HolyOilsBatchMode::INVENTORY_SET => $this->holyOilSetPreviewService->build($character, $validated),
        };
    }

    /**
     * Resolve the mode-specific starting progress data and any blockers preventing the start.
     *
     * @param Character $character The character starting the run.
     * @param array $validated The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    public function resolveStart(Character $character, array $validated): array
    {
        $mode = HolyOilsBatchMode::from($validated['progress']['holy_oils_mode']);

        return match ($mode) {
            HolyOilsBatchMode::SELECTED_ITEMS => $this->resolveSelectedItemsStart($character, $validated),
            HolyOilsBatchMode::INVENTORY_SET => $this->resolveSetStart($character, $validated),
        };
    }

    /**
     * Resolve the starting progress data and blockers for a Holy Oils Selected Items run.
     *
     * @param Character $character The character starting the run.
     * @param array $validated The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveSelectedItemsStart(Character $character, array $validated): array
    {
        $clientProgress = $validated['progress'];
        $inventory = $character->inventory;

        $targets = is_null($inventory)
            ? collect()
            : InventorySlot::where('inventory_id', $inventory->id)
                ->whereIn('id', $clientProgress['target_slot_ids'])
                ->get(['id', 'item_id']);

        $plan = $targets->map(fn (InventorySlot $slot) => new HolyOilApplicationPlanEntry(
            HolyOilTargetKind::INVENTORY_SLOT,
            $slot->id,
            $slot->item_id,
        ))->values()->all();

        $blockers = empty($plan) ? ['None of the selected target items could be found.'] : [];
        $firstTarget = $plan[0] ?? null;

        $progress = [
            'holy_oils_mode' => $clientProgress['holy_oils_mode'],
            'oil_slot_ids' => $clientProgress['oil_slot_ids'],
            'plan' => array_map(fn (HolyOilApplicationPlanEntry $entry) => $entry->toArray(), $plan),
            'plan_index' => 0,
            'current_target_slot_id' => $firstTarget?->targetSlotId,
            'current_target_item_id' => null,
            'current_target_item_name' => null,
            'current_oil_item_id' => null,
            'current_oil_item_name' => null,
            'current_holy_stacks' => null,
            'max_holy_stacks' => null,
            'applications_completed' => 0,
        ];

        return ['progress' => $this->withListingPrice($progress, $validated), 'blockers' => $blockers];
    }

    /**
     * Resolve the starting progress data and blockers for a Holy Oils Inventory Set run.
     *
     * @param Character $character The character starting the run.
     * @param array $validated The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveSetStart(Character $character, array $validated): array
    {
        $clientProgress = $validated['progress'];
        $set = $this->characterInventoryService->setCharacter($character)->resolveValidTargetInventorySet($clientProgress['inventory_set_id']);

        if (is_null($set)) {
            return ['progress' => [], 'blockers' => ['The selected Inventory Set is no longer available.']];
        }

        $setSlots = SetSlot::where('inventory_set_id', $set->id)->get(['id', 'item_id']);

        $plan = $setSlots->map(fn (SetSlot $slot) => new HolyOilApplicationPlanEntry(
            HolyOilTargetKind::SET_SLOT,
            $slot->id,
            $slot->item_id,
        ))->values()->all();

        $blockers = empty($plan) ? ['The selected Set has no target items.'] : [];
        $firstTarget = $plan[0] ?? null;

        $progress = [
            'holy_oils_mode' => $clientProgress['holy_oils_mode'],
            'inventory_set_id' => $set->id,
            'inventory_set_name' => $set->name ?? ('Set '.$set->id),
            'oil_slot_ids' => $clientProgress['oil_slot_ids'],
            'plan' => array_map(fn (HolyOilApplicationPlanEntry $entry) => $entry->toArray(), $plan),
            'plan_index' => 0,
            'current_target_slot_id' => $firstTarget?->targetSlotId,
            'current_target_item_id' => null,
            'current_target_item_name' => null,
            'current_oil_item_id' => null,
            'current_oil_item_name' => null,
            'current_holy_stacks' => null,
            'max_holy_stacks' => null,
            'applications_completed' => 0,
        ];

        return ['progress' => $this->withListingPrice($progress, $validated), 'blockers' => $blockers];
    }

    /**
     * Add the requested listing price to the starting progress data, only when listing is selected.
     *
     * @param array $progress The starting progress data being built.
     * @param array $validated The validated Batch Crafting request data.
     * @return array The starting progress data, including the listing price when applicable.
     */
    private function withListingPrice(array $progress, array $validated): array
    {
        if (BatchCraftingDisposition::from($validated['disposition']) === BatchCraftingDisposition::LIST) {
            $progress['listing_price'] = $validated['progress']['listing_price'];
        }

        return $progress;
    }
}
