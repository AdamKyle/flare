<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;

class AlchemyAmountStatusSection implements BatchCraftingStatusSection
{
    /**
     * Determine whether this section builds the mode-specific status facts for the given type and mode.
     *
     * @param  BatchCraftingType  $type  The batch's Batch Crafting type.
     * @param  string  $mode  The batch's persisted mode value.
     * @return bool True when this section owns the given type and mode.
     */
    public function supports(BatchCraftingType $type, string $mode): bool
    {
        return $type === BatchCraftingType::ALCHEMY && $mode === AlchemyBatchMode::AMOUNT->value;
    }

    /**
     * Build the Alchemy Amount mode-specific status facts for the batch.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  BatchCrafting  $batchCrafting  The visible Batch Crafting record.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array The Alchemy Amount mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        $requested = $progress['alchemy_amount'];
        $completed = $progress['completed_amount'];
        $remaining = max(0, $requested - $completed);

        return [
            'current_item_id' => $progress['current_item_id'],
            'current_item_name' => $progress['current_item_name'],
            'current_crafting_type' => null,
            'destination_set_id' => null,
            'destination_set_name' => null,
            'destination_capacity' => null,
            'requested_amount' => $requested,
            'completed_amount' => $completed,
            'remaining_amount' => $remaining,
            'set_progress' => null,
            'experience_progress' => null,
            'event_progress' => null,
            'alchemy_amount_progress' => [
                'current_item_id' => $progress['current_item_id'],
                'current_item_name' => $progress['current_item_name'],
                'requested_amount' => $requested,
                'completed_amount' => $completed,
                'remaining_amount' => $remaining,
                'alchemy_xp_gained' => $progress['alchemy_xp_gained'],
            ],
        ];
    }
}
