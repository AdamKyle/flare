<?php

namespace App\Game\Core\Items\Values;

use App\Flare\Models\Item;

class ItemUniqueness
{
    private function __construct(private readonly bool $isUnique) {}

    /**
     * Resolve the uniqueness decision for the given Item from its suffix/prefix affixes.
     */
    public static function fromItem(Item $item): ItemUniqueness
    {
        if (! is_null($item->item_suffix_id)) {
            return new ItemUniqueness($item->itemSuffix->randomly_generated);
        }

        if (! is_null($item->item_prefix_id)) {
            return new ItemUniqueness($item->itemPrefix->randomly_generated);
        }

        return new ItemUniqueness(false);
    }

    /**
     * Determine whether the resolved Item is unique.
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }
}
