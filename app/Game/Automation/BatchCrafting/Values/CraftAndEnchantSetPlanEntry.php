<?php

namespace App\Game\Automation\BatchCrafting\Values;

use App\Game\Automation\BatchCrafting\Enums\CraftSetPosition;

class CraftAndEnchantSetPlanEntry
{
    public function __construct(
        public readonly CraftSetPosition $position,
        public readonly int $itemId,
        public readonly string $craftingType,
        public readonly string $itemName,
        public readonly ?int $prefixId,
        public readonly ?int $suffixId,
    ) {}

    /**
     * Build a plan entry from its persisted array shape.
     *
     * @param  array{position: string, item_id: int, crafting_type: string, item_name: string, prefix_id: int|null, suffix_id: int|null}  $entry  The persisted queue entry.
     * @return self The typed plan entry.
     */
    public static function fromArray(array $entry): self
    {
        return new self(
            CraftSetPosition::from($entry['position']),
            $entry['item_id'],
            $entry['crafting_type'],
            $entry['item_name'],
            $entry['prefix_id'],
            $entry['suffix_id'],
        );
    }

    /**
     * Return this plan entry's persisted array shape.
     *
     * @return array{position: string, item_id: int, crafting_type: string, item_name: string, prefix_id: int|null, suffix_id: int|null} The persisted queue entry.
     */
    public function toArray(): array
    {
        return [
            'position' => $this->position->value,
            'item_id' => $this->itemId,
            'crafting_type' => $this->craftingType,
            'item_name' => $this->itemName,
            'prefix_id' => $this->prefixId,
            'suffix_id' => $this->suffixId,
        ];
    }

    /**
     * Determine whether this plan entry has at least one requested enchantment.
     *
     * @return bool True when a Prefix or Suffix is selected for this entry.
     */
    public function hasEnchantment(): bool
    {
        return ! is_null($this->prefixId) || ! is_null($this->suffixId);
    }
}
