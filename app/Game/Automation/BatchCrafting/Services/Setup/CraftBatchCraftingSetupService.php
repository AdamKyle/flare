<?php

namespace App\Game\Automation\BatchCrafting\Services\Setup;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Services\Capabilities\CraftBatchCraftingCapabilityService;
use App\Game\Automation\BatchCrafting\Services\CraftAmountPreviewService;
use App\Game\Automation\BatchCrafting\Services\CraftSetPreviewService;

class CraftBatchCraftingSetupService implements BatchCraftingSetupService
{
    public function __construct(
        private readonly CraftAmountPreviewService $craftAmountPreviewService,
        private readonly CraftSetPreviewService $craftSetPreviewService,
        private readonly CraftBatchCraftingCapabilityService $craftBatchCraftingCapabilityService,
    ) {}

    /**
     * Determine whether this setup service owns preview/start resolution for the given Batch Crafting type.
     *
     * @param BatchCraftingType $type The requested Batch Crafting type.
     * @return bool True when this setup service owns the given type.
     */
    public function supports(BatchCraftingType $type): bool
    {
        return $type === BatchCraftingType::CRAFT;
    }

    /**
     * Build the preview result for the validated request, for craft modes that support a preview.
     *
     * @param Character $character The character requesting the preview.
     * @param array $validated The validated Batch Crafting request data.
     * @return array|null The preview payload, or null when no preview is available for the requested craft mode.
     */
    public function preview(Character $character, array $validated): ?array
    {
        $mode = CraftingBatchMode::from($validated['progress']['craft_mode']);

        return match ($mode) {
            CraftingBatchMode::AMOUNT => $this->craftAmountPreviewService->build($character, $validated),
            CraftingBatchMode::SET => $this->craftSetPreviewService->build($character, $validated['progress'], $validated['disposition']),
            default => null,
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
        $mode = CraftingBatchMode::from($validated['progress']['craft_mode']);

        return match ($mode) {
            CraftingBatchMode::AMOUNT => $this->resolveAmountStart($character, $validated),
            CraftingBatchMode::SET => $this->resolveSetStart($character, $validated),
            CraftingBatchMode::EXPERIENCE => $this->resolveExperienceStart($character, $validated),
            CraftingBatchMode::EVENT => $this->resolveEventStart($character, $validated),
        };
    }

    /**
     * Resolve the starting progress data and blockers for a Craft Amount run.
     *
     * @param Character $character The character starting the run.
     * @param array $validated The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveAmountStart(Character $character, array $validated): array
    {
        $preview = $this->craftAmountPreviewService->build($character, $validated);
        $clientProgress = $validated['progress'];

        $progress = [
            'craft_mode' => $clientProgress['craft_mode'],
            'specific_crafting_type' => $clientProgress['specific_crafting_type'],
            'specific_item_id' => $clientProgress['specific_item_id'],
            'craft_amount' => $clientProgress['craft_amount'],
            'output_destination' => $clientProgress['output_destination'] ?? null,
            'craft_specific_count' => 0,
        ];

        return ['progress' => $progress, 'blockers' => $preview['blockers']];
    }

    /**
     * Resolve the starting progress data and blockers for a Craft Set run.
     *
     * @param Character $character The character starting the run.
     * @param array $validated The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveSetStart(Character $character, array $validated): array
    {
        $clientProgress = $validated['progress'];
        $preview = $this->craftSetPreviewService->build($character, $clientProgress, $validated['disposition']);

        $progress = [
            'craft_mode' => $clientProgress['craft_mode'],
            'set_positions' => $clientProgress['set_positions'],
            'output_destination' => $clientProgress['output_destination'] ?? null,
            'output_set_id' => $clientProgress['output_set_id'] ?? null,
            'set_queue' => $preview['positions'],
            'set_index' => 0,
        ];

        return ['progress' => $progress, 'blockers' => $preview['blockers']];
    }

    /**
     * Resolve the starting progress data and blockers for a Craft For Experience run.
     *
     * @param Character $character The character starting the run.
     * @param array $validated The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveExperienceStart(Character $character, array $validated): array
    {
        $blockers = [];

        if (! $this->craftBatchCraftingCapabilityService->canCraftForExperience($character)) {
            $blockers[] = 'You do not have a Crafting skill that can still gain levels, or nothing currently provides meaningful Crafting XP.';
        }

        $disposition = BatchCraftingDisposition::from($validated['disposition']);

        $progress = [
            'craft_mode' => $validated['progress']['craft_mode'],
            'cycle_position' => 0,
            'crafting_xp_gained' => 0,
        ];

        if ($disposition->keepsBest()) {
            $progress['kept_best'] = [];
        }

        return ['progress' => $progress, 'blockers' => $blockers];
    }

    /**
     * Resolve the starting progress data and blockers for a Craft For Event run.
     *
     * @param Character $character The character starting the run.
     * @param array $validated The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveEventStart(Character $character, array $validated): array
    {
        $blockers = [];

        if (! $this->craftBatchCraftingCapabilityService->canCraftForEvent($character)) {
            $blockers[] = 'There is no currently eligible Craft Event to contribute to.';
        }

        $goalFacts = $this->craftBatchCraftingCapabilityService->eventGoalFacts($character);

        $progress = [
            'craft_mode' => $validated['progress']['craft_mode'],
            'event_goal_id' => $goalFacts['goal_id'] ?? null,
            'event_cycle_position' => 0,
            'crafting_xp_gained' => 0,
            'current_item_id' => null,
            'current_item_name' => null,
            'current_crafting_type' => null,
        ];

        return ['progress' => $progress, 'blockers' => $blockers];
    }
}
