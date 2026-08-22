<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\HolyOilsBatchMode;

class HolyOilSetStatusSection implements BatchCraftingStatusSection
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
        return $type === BatchCraftingType::HOLY_OILS && $mode === HolyOilsBatchMode::INVENTORY_SET->value;
    }

    /**
     * Build the Holy Oils Inventory Set mode-specific status facts for the batch.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  BatchCrafting  $batchCrafting  The visible Batch Crafting record.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array The Holy Oils Inventory Set mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        $totalPlanned = count($progress['plan']);
        $completed = $progress['plan_index'];

        return [
            'current_item_id' => $progress['current_target_item_id'],
            'current_item_name' => $progress['current_target_item_name'],
            'current_crafting_type' => null,
            'destination_set_id' => $progress['inventory_set_id'],
            'destination_set_name' => $progress['inventory_set_name'],
            'destination_capacity' => null,
            'requested_amount' => null,
            'completed_amount' => null,
            'remaining_amount' => null,
            'set_progress' => null,
            'experience_progress' => null,
            'event_progress' => null,
            'holy_oils_progress' => [
                'current_target_item_id' => $progress['current_target_item_id'],
                'current_target_item_name' => $progress['current_target_item_name'],
                'current_oil_item_id' => $progress['current_oil_item_id'],
                'current_oil_item_name' => $progress['current_oil_item_name'],
                'current_holy_stacks' => $progress['current_holy_stacks'],
                'max_holy_stacks' => $progress['max_holy_stacks'],
                'total_planned_targets' => $totalPlanned,
                'completed_targets' => min($completed, $totalPlanned),
                'remaining_targets' => max(0, $totalPlanned - $completed),
                'inventory_set_id' => $progress['inventory_set_id'],
                'inventory_set_name' => $progress['inventory_set_name'],
            ],
        ];
    }
}
