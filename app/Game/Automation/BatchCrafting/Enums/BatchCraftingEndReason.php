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
    case SKILL_MAXED = 'skill_maxed';
    case CRAFT_SET_COMPLETE = 'craft_set_complete';
    case CRAFT_SET_FULL = 'craft_set_full';
    case EVENT_GOAL_COMPLETE = 'event_goal_complete';
    case EVENT_NOT_RUNNING = 'event_not_running';
    case EVENT_WRONG_MAP = 'event_wrong_map';
    case EVENT_STEP_CHANGED = 'event_step_changed';
    case EVENT_NO_CRAFTABLE_ITEMS = 'event_no_craftable_items';
    case NO_GOLD_DUST = 'no_gold_dust';
    case NO_SHARDS = 'no_shards';
    case NO_COPPER_COINS = 'no_copper_coins';
    case NO_ENCHANTING_AFFIX = 'no_enchanting_affix';
    case INT_TOO_LOW = 'int_too_low';
    case NO_ALCHEMY_ITEMS = 'no_alchemy_items';
    case NO_HOLY_OILS = 'no_holy_oils';
    case NO_HOLY_OIL_TARGETS = 'no_holy_oil_targets';
    case NO_TRINKETRY_ITEMS = 'no_trinketry_items';
}
