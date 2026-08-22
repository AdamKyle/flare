<?php

namespace App\Game\Automation\BatchCrafting\Services\Setup;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\AlchemyAmountPreviewService;
use App\Game\Skills\Services\AlchemyService;

class AlchemyBatchCraftingSetupService implements BatchCraftingSetupService
{
    public function __construct(
        private readonly AlchemyAmountPreviewService $alchemyAmountPreviewService,
        private readonly AlchemyService $alchemyService,
    ) {}

    /**
     * Determine whether this setup service owns preview/start resolution for the given Batch Crafting type.
     *
     * @param  BatchCraftingType  $type  The requested Batch Crafting type.
     * @return bool True when this setup service owns the given type.
     */
    public function supports(BatchCraftingType $type): bool
    {
        return $type === BatchCraftingType::ALCHEMY;
    }

    /**
     * Build the preview result for the validated request, for Alchemy modes that support a preview.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array|null The preview payload, or null when no preview is available for the requested Alchemy mode.
     */
    public function preview(Character $character, array $validated): ?array
    {
        $mode = AlchemyBatchMode::from($validated['progress']['alchemy_mode']);

        return match ($mode) {
            AlchemyBatchMode::AMOUNT => $this->alchemyAmountPreviewService->build($character, $validated),
            AlchemyBatchMode::EXPERIENCE => null,
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
        $mode = AlchemyBatchMode::from($validated['progress']['alchemy_mode']);

        return match ($mode) {
            AlchemyBatchMode::AMOUNT => $this->resolveAmountStart($character, $validated),
            AlchemyBatchMode::EXPERIENCE => $this->resolveExperienceStart($character, $validated),
        };
    }

    /**
     * Resolve the starting progress data and blockers for an Alchemy Amount run.
     *
     * @param  Character  $character  The character starting the run.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveAmountStart(Character $character, array $validated): array
    {
        $preview = $this->alchemyAmountPreviewService->build($character, $validated);
        $clientProgress = $validated['progress'];

        $progress = [
            'alchemy_mode' => $clientProgress['alchemy_mode'],
            'alchemy_item_id' => $clientProgress['alchemy_item_id'],
            'alchemy_amount' => $clientProgress['alchemy_amount'],
            'completed_amount' => 0,
            'alchemy_xp_gained' => 0,
            'current_item_id' => null,
            'current_item_name' => null,
        ];

        return ['progress' => $this->withListingPrice($progress, $validated), 'blockers' => $preview['blockers']];
    }

    /**
     * Resolve the starting progress data and blockers for an Alchemy For Experience run.
     *
     * @param  Character  $character  The character starting the run.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    private function resolveExperienceStart(Character $character, array $validated): array
    {
        $blockers = [];

        if (is_null($this->alchemyService->findMeaningfulBatchItem($character))) {
            $blockers[] = 'There is no Alchemy item that can still provide meaningful XP.';
        }

        $clientProgress = $validated['progress'];

        $progress = [
            'alchemy_mode' => $clientProgress['alchemy_mode'],
            'alchemy_xp_gained' => 0,
            'current_item_id' => null,
            'current_item_name' => null,
        ];

        return ['progress' => $this->withListingPrice($progress, $validated), 'blockers' => $blockers];
    }

    /**
     * Add the requested listing price to the starting progress data, only when listing is selected.
     *
     * @param  array  $progress  The starting progress data being built.
     * @param  array  $validated  The validated Batch Crafting request data.
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
