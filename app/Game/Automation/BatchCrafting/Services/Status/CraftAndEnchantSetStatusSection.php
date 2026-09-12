<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;

class CraftAndEnchantSetStatusSection implements BatchCraftingStatusSection
{
    public function __construct(private readonly BatchCraftingDestinationResolver $destinationResolver) {}

    /**
     * Determine whether this section builds the mode-specific status facts for the given type and mode.
     *
     * @param BatchCraftingType $type The batch's Batch Crafting type.
     * @param string $mode The batch's persisted mode value.
     * @return bool True when this section owns the given type and mode.
     */
    public function supports(BatchCraftingType $type, string $mode): bool
    {
        return $type === BatchCraftingType::CRAFT_AND_ENCHANT && $mode === CraftAndEnchantBatchMode::SET->value;
    }

    /**
     * Build the Craft and Enchant Set mode-specific status facts for the batch.
     *
     * @param Character $character The character the batch belongs to.
     * @param BatchCrafting $batchCrafting The visible Batch Crafting record.
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array The Craft and Enchant Set mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $destination = $this->destinationResolver->resolve($character, $disposition, $progress);

        return [
            'current_item_id' => $progress['current_item_id'] ?? null,
            'current_item_name' => $progress['current_item_name'] ?? null,
            'current_crafting_type' => null,
            'current_prefix_name' => $progress['current_prefix_name'] ?? null,
            'current_suffix_name' => $progress['current_suffix_name'] ?? null,
            'destination_set_id' => $destination['destination_set_id'],
            'destination_set_name' => $destination['destination_set_name'],
            'destination_capacity' => $destination['capacity'],
            'requested_amount' => null,
            'completed_amount' => null,
            'remaining_amount' => null,
            'set_progress' => $this->buildSetProgress($progress),
            'experience_progress' => null,
            'event_progress' => null,
        ];
    }

    /**
     * Build the factual Craft and Enchant Set progress section.
     *
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array The Craft and Enchant Set progress facts.
     */
    private function buildSetProgress(array $progress): array
    {
        $queue = $progress['set_queue'];
        $index = $progress['set_index'];

        return [
            'total_entries' => count($queue),
            'completed_entries' => $index,
            'remaining_entries' => max(0, count($queue) - $index),
            'current_position' => $progress['current_position'] ?? ($queue[$index]['position'] ?? null),
            'current_phase' => $progress['set_phase'],
        ];
    }
}
