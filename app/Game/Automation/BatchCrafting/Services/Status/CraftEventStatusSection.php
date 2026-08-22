<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Services\Capabilities\CraftBatchCraftingCapabilityService;

class CraftEventStatusSection implements BatchCraftingStatusSection
{
    public function __construct(private readonly CraftBatchCraftingCapabilityService $craftBatchCraftingCapabilityService) {}

    /**
     * Determine whether this section builds the mode-specific status facts for the given type and mode.
     *
     * @param  BatchCraftingType  $type  The batch's Batch Crafting type.
     * @param  string  $mode  The batch's persisted mode value.
     * @return bool True when this section owns the given type and mode.
     */
    public function supports(BatchCraftingType $type, string $mode): bool
    {
        return $type === BatchCraftingType::CRAFT && $mode === CraftingBatchMode::EVENT->value;
    }

    /**
     * Build the Craft For Event mode-specific status facts for the batch.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  BatchCrafting  $batchCrafting  The visible Batch Crafting record.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array The Craft For Event mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        $goal = $this->resolveFinalEventGoalFacts($character, $progress);

        return [
            'current_item_id' => $progress['current_item_id'] ?? null,
            'current_item_name' => $progress['current_item_name'] ?? null,
            'current_crafting_type' => $progress['current_crafting_type'] ?? null,
            'destination_set_id' => null,
            'destination_set_name' => null,
            'destination_capacity' => null,
            'requested_amount' => null,
            'completed_amount' => null,
            'remaining_amount' => null,
            'set_progress' => null,
            'experience_progress' => null,
            'event_progress' => [
                'actions_per_minute' => CraftingBatchMode::EVENT->executionWindowSize(),
                'current_crafting_type' => $progress['current_crafting_type'] ?? null,
                'skipped_count' => $batchCrafting->skipped_count,
                'crafting_xp_gained' => $progress['crafting_xp_gained'],
                'goal_id' => $goal['goal_id'] ?? null,
                'max_crafts' => $goal['max_crafts'] ?? null,
                'total_crafts' => $goal['total_crafts'] ?? null,
                'next_reward_at' => $goal['next_reward_at'] ?? null,
                'reward_every' => $goal['reward_every'] ?? null,
                'character_contribution' => $goal['character_contribution'] ?? null,
            ],
        ];
    }

    /**
     * Resolve the Craft Event goal/contribution facts by the batch's own authoritative persisted goal id.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array|null The goal/contribution facts, or null when no goal was ever persisted.
     */
    private function resolveFinalEventGoalFacts(Character $character, array $progress): ?array
    {
        $goalId = $progress['event_goal_id'] ?? null;

        if (is_null($goalId)) {
            return null;
        }

        return $this->craftBatchCraftingCapabilityService->eventGoalFactsById($character, $goalId);
    }
}
