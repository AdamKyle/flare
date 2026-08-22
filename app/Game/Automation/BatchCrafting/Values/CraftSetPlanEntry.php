<?php

namespace App\Game\Automation\BatchCrafting\Values;

use App\Game\Automation\BatchCrafting\Enums\CraftSetPosition;

class CraftSetPlanEntry
{
    public function __construct(
        public readonly CraftSetPosition $position,
        public readonly int $itemId,
        public readonly string $craftingType,
        public readonly string $itemName,
    ) {}

    /**
     * Build a plan entry from its persisted array shape.
     *
     * @param  array{position: string, item_id: int, crafting_type: string, item_name: string}  $entry  The persisted queue entry.
     * @return self The typed plan entry.
     */
    public static function fromArray(array $entry): self
    {
        return new self(
            CraftSetPosition::from($entry['position']),
            $entry['item_id'],
            $entry['crafting_type'],
            $entry['item_name'],
        );
    }

    /**
     * Return this plan entry's persisted array shape.
     *
     * @return array{position: string, item_id: int, crafting_type: string, item_name: string} The persisted queue entry.
     */
    public function toArray(): array
    {
        return [
            'position' => $this->position->value,
            'item_id' => $this->itemId,
            'crafting_type' => $this->craftingType,
            'item_name' => $this->itemName,
        ];
    }
}
