<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;

class CraftAndEnchantAmountStatusSection implements BatchCraftingStatusSection
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
        return $type === BatchCraftingType::CRAFT_AND_ENCHANT && $mode === CraftAndEnchantBatchMode::AMOUNT->value;
    }

    /**
     * Build the Craft and Enchant Amount mode-specific status facts for the batch.
     *
     * @param Character $character The character the batch belongs to.
     * @param BatchCrafting $batchCrafting The visible Batch Crafting record.
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array The Craft and Enchant Amount mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        $item = Item::find($progress['specific_item_id']);
        $prefix = is_null($progress['prefix_id']) ? null : ItemAffix::find($progress['prefix_id']);
        $suffix = is_null($progress['suffix_id']) ? null : ItemAffix::find($progress['suffix_id']);
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $destination = $this->destinationResolver->resolve($character, $disposition, $progress);
        $requested = $progress['craft_amount'];
        $completed = $progress['completed_amount'];

        return [
            'current_item_id' => $progress['specific_item_id'],
            'current_item_name' => $item?->affix_name ?? $item?->name,
            'current_crafting_type' => $progress['specific_crafting_type'],
            'current_prefix_name' => $prefix?->name,
            'current_suffix_name' => $suffix?->name,
            'destination_set_id' => $destination['destination_set_id'],
            'destination_set_name' => $destination['destination_set_name'],
            'destination_capacity' => $destination['capacity'],
            'requested_amount' => $requested,
            'completed_amount' => $completed,
            'remaining_amount' => max(0, $requested - $completed),
            'set_progress' => null,
            'experience_progress' => null,
            'event_progress' => null,
        ];
    }
}
