<?php

namespace App\Game\Automation\BatchCrafting\Services\Setup;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantSetPhase;
use App\Game\Automation\BatchCrafting\Services\Capabilities\CraftAndEnchantBatchCraftingCapabilityService;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantAmountPreviewService;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantSetPlanService;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantSetPreviewService;

class CraftAndEnchantBatchCraftingSetupService implements BatchCraftingSetupService
{
    public function __construct(
        private readonly CraftAndEnchantAmountPreviewService $craftAndEnchantAmountPreviewService,
        private readonly CraftAndEnchantSetPreviewService $craftAndEnchantSetPreviewService,
        private readonly CraftAndEnchantSetPlanService $craftAndEnchantSetPlanService,
        private readonly CraftAndEnchantBatchCraftingCapabilityService $craftAndEnchantBatchCraftingCapabilityService,
    ) {}

    /**
     * Determine whether this setup service owns preview/start resolution for the given Batch Crafting type.
     *
     * @param  BatchCraftingType  $type  The requested Batch Crafting type.
     * @return bool True when this setup service owns the given type.
     */
    public function supports(BatchCraftingType $type): bool
    {
        return $type === BatchCraftingType::CRAFT_AND_ENCHANT;
    }

    /**
     * Build the preview result for the validated request, for modes that support a preview.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array|null The preview payload, or null when no preview is available for the requested mode.
     */
    public function preview(Character $character, array $validated): ?array
    {
        $mode = CraftAndEnchantBatchMode::from($validated['progress']['craft_enchant_mode']);

        return match ($mode) {
            CraftAndEnchantBatchMode::AMOUNT => $this->craftAndEnchantAmountPreviewService->build($character, $validated),
            CraftAndEnchantBatchMode::SET => $this->craftAndEnchantSetPreviewService->build($character, $validated['progress'], $validated['disposition']),
            default => null,
        };
    }

    /**
     * Resolve the mode-specific starting progress data and any blockers preventing the start.
     *
     * @param  Character  $character  The character starting the run.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    public function resolveStart(Character $character, array $validated): array
    {
        $mode = CraftAndEnchantBatchMode::from($validated['progress']['craft_enchant_mode']);

        return match ($mode) {
            CraftAndEnchantBatchMode::AMOUNT => $this->resolveAmountStart($character, $validated),
            CraftAndEnchantBatchMode::EXPERIENCE => $this->resolveExperienceStart($character, $validated),
            CraftAndEnchantBatchMode::SET => $this->resolveSetStart($character, $validated),
        };
    }

    /**
     * Resolve the starting progress data and blockers for a Craft and Enchant Set run.
     *
     * @param  Character  $character  The character starting the run.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveSetStart(Character $character, array $validated): array
    {
        $clientProgress = $validated['progress'];
        $preview = $this->craftAndEnchantSetPreviewService->build($character, $clientProgress, $validated['disposition']);
        $plan = $this->craftAndEnchantSetPlanService->resolvePlan($character, $clientProgress['set_positions'] ?? [], $clientProgress['enchantments'] ?? []);

        $progress = [
            'craft_enchant_mode' => $clientProgress['craft_enchant_mode'],
            'set_queue' => $plan['queue'],
            'set_index' => 0,
            'set_phase' => CraftAndEnchantSetPhase::CRAFTING->value,
            'output_destination' => $clientProgress['output_destination'] ?? null,
            'output_set_id' => $clientProgress['output_set_id'] ?? null,
            'listing_price' => $clientProgress['listing_price'] ?? null,
            'current_item_id' => null,
            'current_item_name' => null,
            'current_position' => null,
            'current_prefix_name' => null,
            'current_suffix_name' => null,
        ];

        return ['progress' => $progress, 'blockers' => $preview['blockers']];
    }

    /**
     * Resolve the starting progress data and blockers for a Craft and Enchant For Experience run.
     *
     * @param  Character  $character  The character starting the run.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveExperienceStart(Character $character, array $validated): array
    {
        $blockers = [];

        if (! $this->craftAndEnchantBatchCraftingCapabilityService->build($character)['can_craft_and_enchant_for_experience']) {
            $blockers[] = 'You do not currently have meaningful Crafting or Enchanting progression available for this workflow.';
        }

        $clientProgress = $validated['progress'];
        $disposition = BatchCraftingDisposition::from($validated['disposition']);

        $progress = [
            'craft_enchant_mode' => $clientProgress['craft_enchant_mode'],
            'cycle_position' => 0,
            'crafting_xp_gained' => 0,
            'enchanting_xp_gained' => 0,
            'current_item_id' => null,
            'current_item_name' => null,
            'current_crafting_type' => null,
            'current_prefix_name' => null,
            'current_suffix_name' => null,
            'listing_price' => $clientProgress['listing_price'] ?? null,
        ];

        if ($disposition->keepsBest()) {
            $progress['kept_best'] = [];
        }

        return ['progress' => $progress, 'blockers' => $blockers];
    }

    /**
     * Resolve the starting progress data and blockers for a Craft and Enchant Amount run.
     *
     * @param  Character  $character  The character starting the run.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveAmountStart(Character $character, array $validated): array
    {
        if (! $this->craftAndEnchantBatchCraftingCapabilityService->build($character)['can_craft_and_enchant']) {
            return ['progress' => [], 'blockers' => ['You do not currently have the Crafting and Enchanting prerequisites for this workflow.']];
        }

        $preview = $this->craftAndEnchantAmountPreviewService->build($character, $validated);
        $clientProgress = $validated['progress'];

        $progress = [
            'craft_enchant_mode' => $clientProgress['craft_enchant_mode'],
            'specific_crafting_type' => $clientProgress['specific_crafting_type'],
            'specific_item_id' => $clientProgress['specific_item_id'],
            'prefix_id' => $clientProgress['prefix_id'] ?? null,
            'suffix_id' => $clientProgress['suffix_id'] ?? null,
            'craft_amount' => $clientProgress['craft_amount'],
            'completed_amount' => 0,
            'output_destination' => $clientProgress['output_destination'] ?? null,
            'output_set_id' => $clientProgress['output_set_id'] ?? null,
            'listing_price' => $clientProgress['listing_price'] ?? null,
            'current_item_id' => null,
            'current_item_name' => null,
            'current_prefix_name' => null,
            'current_suffix_name' => null,
        ];

        return ['progress' => $progress, 'blockers' => $preview['blockers']];
    }
}
