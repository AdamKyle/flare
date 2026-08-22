<?php

namespace App\Game\Automation\BatchCrafting\Services\Setup;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\EnchantEventPhase;
use App\Game\Automation\BatchCrafting\Services\Capabilities\EnchantBatchCraftingCapabilityService;

class EnchantBatchCraftingSetupService implements BatchCraftingSetupService
{
    public function __construct(
        private readonly EnchantBatchCraftingCapabilityService $enchantBatchCraftingCapabilityService,
    ) {}

    /**
     * Determine whether this setup service owns preview/start resolution for the given Batch Crafting type.
     *
     * @param  BatchCraftingType  $type  The requested Batch Crafting type.
     * @return bool True when this setup service owns the given type.
     */
    public function supports(BatchCraftingType $type): bool
    {
        return $type === BatchCraftingType::ENCHANT;
    }

    /**
     * Build the preview result for the validated request.
     *
     * Enchant For Event requires no manual item or affix selection, so no preview payload applies.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array|null Always null; Enchant For Event has no preview.
     */
    public function preview(Character $character, array $validated): ?array
    {
        return null;
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
        $blockers = [];
        $capability = $this->enchantBatchCraftingCapabilityService->build($character);

        if (! $capability['can_enchant_for_event']) {
            $blockers[] = 'There is no currently eligible Enchant Event to contribute to.';
        }

        $progress = [
            'enchant_mode' => $validated['progress']['enchant_mode'],
            'event_goal_id' => $capability['enchant_event_goal']['goal_id'] ?? null,
            'event_enchant_phase' => EnchantEventPhase::ENCHANT_EVENT_INVENTORY->value,
            'current_item_id' => null,
            'current_item_name' => null,
            'current_prefix_name' => null,
            'current_suffix_name' => null,
            'enchanting_xp_gained' => 0,
            'crafting_xp_gained' => 0,
            'fallback_cycle_position' => 0,
        ];

        return ['progress' => $progress, 'blockers' => $blockers];
    }
}
