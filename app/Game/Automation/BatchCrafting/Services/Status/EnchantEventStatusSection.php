<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\EnchantingBatchMode;
use App\Game\Automation\BatchCrafting\Services\Capabilities\EnchantBatchCraftingCapabilityService;

class EnchantEventStatusSection implements BatchCraftingStatusSection
{
    public function __construct(private readonly EnchantBatchCraftingCapabilityService $enchantBatchCraftingCapabilityService) {}

    /**
     * Determine whether this section builds the mode-specific status facts for the given type and mode.
     *
     * @param BatchCraftingType $type The batch's Batch Crafting type.
     * @param string $mode The batch's persisted mode value.
     * @return bool True when this section owns the given type and mode.
     */
    public function supports(BatchCraftingType $type, string $mode): bool
    {
        return $type === BatchCraftingType::ENCHANT && $mode === EnchantingBatchMode::EVENT->value;
    }

    /**
     * Build the Enchant For Event mode-specific status facts for the batch.
     *
     * @param Character $character The character the batch belongs to.
     * @param BatchCrafting $batchCrafting The visible Batch Crafting record.
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array The Enchant For Event mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        $goal = $this->resolveFinalEventGoalFacts($character, $progress);

        return [
            'current_item_id' => $progress['current_item_id'] ?? null,
            'current_item_name' => $progress['current_item_name'] ?? null,
            'current_crafting_type' => null,
            'current_prefix_name' => $progress['current_prefix_name'] ?? null,
            'current_suffix_name' => $progress['current_suffix_name'] ?? null,
            'destination_set_id' => null,
            'destination_set_name' => null,
            'destination_capacity' => null,
            'requested_amount' => null,
            'completed_amount' => null,
            'remaining_amount' => null,
            'set_progress' => null,
            'experience_progress' => null,
            'event_progress' => [
                'actions_per_minute' => EnchantingBatchMode::EVENT->executionWindowSize(),
                'phase' => $progress['event_enchant_phase'],
                'enchanting_xp_gained' => $progress['enchanting_xp_gained'],
                'crafting_xp_gained' => $progress['crafting_xp_gained'],
                'goal_id' => $goal['goal_id'] ?? null,
                'event_id' => $goal['event_id'] ?? null,
                'event_type' => $goal['event_type'] ?? null,
                'max_enchants' => $goal['max_enchants'] ?? null,
                'total_enchants' => $goal['total_enchants'] ?? null,
                'remaining_enchants' => $goal['remaining_enchants'] ?? null,
                'character_contribution' => $goal['character_contribution'] ?? null,
                'ends_at' => $goal['ends_at'] ?? null,
            ],
        ];
    }

    /**
     * Resolve the Enchant Event goal/contribution facts by the batch's own authoritative persisted goal id.
     *
     * @param Character $character The character the batch belongs to.
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array|null The goal/contribution facts, or null when no goal was ever persisted.
     */
    private function resolveFinalEventGoalFacts(Character $character, array $progress): ?array
    {
        $goalId = $progress['event_goal_id'] ?? null;

        if (is_null($goalId)) {
            return null;
        }

        return $this->enchantBatchCraftingCapabilityService->eventGoalFactsById($character, $goalId);
    }
}
