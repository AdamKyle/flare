<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum BatchCraftingDisposition: string
{
    case KEEP = 'keep';
    case SELL = 'sell';
    case DESTROY = 'destroy';
    case KEEP_BEST_SELL_REST = 'keep_best_sell_rest';
    case KEEP_BEST_DESTROY_REST = 'keep_best_destroy_rest';

    /**
     * Determine whether this disposition retains only the strongest crafted item per item type.
     *
     * @return bool True for a Keep Best disposition.
     */
    public function keepsBest(): bool
    {
        return $this === self::KEEP_BEST_SELL_REST || $this === self::KEEP_BEST_DESTROY_REST;
    }
}
