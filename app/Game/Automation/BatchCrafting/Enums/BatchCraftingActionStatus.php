<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum BatchCraftingActionStatus: string
{
    case KEPT = 'kept';
    case SOLD = 'sold';
    case DESTROYED = 'destroyed';
    case LISTED = 'listed';
    case DISENCHANTED = 'disenchanted';
    case USED = 'used';
    case APPLIED = 'applied';
    case FAILED = 'failed';
    case CRAFTED = 'crafted';
    case SKIPPED = 'skipped';

    /**
     * Determine whether this action status represents a successfully crafted item.
     *
     * Holy Oil application and Event enchanting application are not crafted items.
     *
     * @return bool True when the item was crafted successfully.
     */
    public function didCraft(): bool
    {
        return match ($this) {
            self::KEPT, self::SOLD, self::DESTROYED, self::LISTED, self::DISENCHANTED, self::USED, self::CRAFTED => true,
            self::APPLIED, self::FAILED, self::SKIPPED => false,
        };
    }
}
