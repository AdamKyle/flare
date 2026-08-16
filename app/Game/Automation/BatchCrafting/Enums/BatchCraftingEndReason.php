<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum BatchCraftingEndReason: string
{
    case COMPLETED_DURATION = 'completed_duration';
    case DIED = 'died';
    case NO_GOLD = 'no_gold';
    case NO_INVENTORY_SPACE = 'no_inventory_space';
    case MAXED_OR_NOTHING_LEFT = 'maxed_or_nothing_left';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case AMOUNT_REACHED = 'amount_reached';
    case BATCH_CRAFTING_SET_FULL = 'batch_crafting_set_full';
}
