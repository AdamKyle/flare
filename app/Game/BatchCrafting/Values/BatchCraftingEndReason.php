<?php

namespace App\Game\BatchCrafting\Values;

enum BatchCraftingEndReason: string
{
    case COMPLETED_DURATION = 'completed_duration';
    case DIED = 'died';
    case NO_GOLD = 'no_gold';
    case NO_GOLD_DUST = 'no_gold_dust';
    case NO_SHARDS = 'no_shards';
    case NO_REQUIRED_CURRENCY = 'no_required_currency';
    case NO_INVENTORY_SPACE = 'no_inventory_space';
    case MAXED_OR_NOTHING_LEFT = 'maxed_or_nothing_left';
    case ALL_OILS_APPLIED = 'all_oils_applied';
    case NO_OILS_LEFT = 'no_oils_left';
    case NO_SELECTED_ITEMS_LEFT = 'no_selected_items_left';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case AMOUNT_REACHED = 'amount_reached';
    case SKILL_MAXED = 'skill_maxed';
    case BATCH_CRAFTING_SET_FULL = 'batch_crafting_set_full';
}
