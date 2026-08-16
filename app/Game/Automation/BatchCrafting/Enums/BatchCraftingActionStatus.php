<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum BatchCraftingActionStatus: string
{
    case KEPT = 'kept';
    case SOLD = 'sold';
    case DESTROYED = 'destroyed';
    case FAILED = 'failed';

    /**
     * Determine whether this action status represents a successfully crafted item.
     *
     * @return bool True when the item was crafted successfully.
     */
    public function didCraft(): bool
    {
        return match ($this) {
            self::KEPT, self::SOLD, self::DESTROYED => true,
            self::FAILED => false,
        };
    }
}
