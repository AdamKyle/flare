<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;
use App\Game\Automation\BatchCrafting\Services\Capabilities\CraftAndEnchantBatchCraftingCapabilityService;
use App\Game\Automation\BatchCrafting\Services\Capabilities\CraftBatchCraftingCapabilityService;
use App\Game\Automation\BatchCrafting\Services\CraftExperienceTargetService;

class CraftAndEnchantExperienceStatusSection implements BatchCraftingStatusSection
{
    public function __construct(
        private readonly CraftExperienceTargetService $craftExperienceTargetService,
        private readonly CraftBatchCraftingCapabilityService $craftBatchCraftingCapabilityService,
        private readonly CraftAndEnchantBatchCraftingCapabilityService $craftAndEnchantBatchCraftingCapabilityService,
    ) {}

    /**
     * Determine whether this section builds the mode-specific status facts for the given type and mode.
     *
     * @param  BatchCraftingType  $type  The batch's Batch Crafting type.
     * @param  string  $mode  The batch's persisted mode value.
     * @return bool True when this section owns the given type and mode.
     */
    public function supports(BatchCraftingType $type, string $mode): bool
    {
        return $type === BatchCraftingType::CRAFT_AND_ENCHANT && $mode === CraftAndEnchantBatchMode::EXPERIENCE->value;
    }

    /**
     * Build the Craft and Enchant For Experience mode-specific status facts for the batch.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  BatchCrafting  $batchCrafting  The visible Batch Crafting record.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array The Craft and Enchant For Experience mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        return [
            'current_item_id' => $progress['current_item_id'] ?? null,
            'current_item_name' => $progress['current_item_name'] ?? null,
            'current_crafting_type' => $progress['current_crafting_type'] ?? null,
            'current_prefix_name' => $progress['current_prefix_name'] ?? null,
            'current_suffix_name' => $progress['current_suffix_name'] ?? null,
            'destination_set_id' => null,
            'destination_set_name' => null,
            'destination_capacity' => null,
            'requested_amount' => null,
            'completed_amount' => null,
            'remaining_amount' => null,
            'set_progress' => null,
            'experience_progress' => [
                'actions_per_minute' => CraftAndEnchantBatchMode::EXPERIENCE->executionWindowSize(),
                'current_cycle_position' => $progress['cycle_position'],
                'cycle_size' => $this->craftExperienceTargetService->cycleSize(),
                'current_crafting_type' => $progress['current_crafting_type'] ?? null,
                'crafting_xp_gained' => $progress['crafting_xp_gained'],
                'enchanting_xp_gained' => $progress['enchanting_xp_gained'],
                'crafting_skills' => $this->craftBatchCraftingCapabilityService->craftingSkillFacts($character),
                'enchanting_skill' => $this->craftAndEnchantBatchCraftingCapabilityService->enchantingSkillFactsFor($character),
            ],
            'event_progress' => null,
        ];
    }
}
