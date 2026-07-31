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
    case EVENT_GOAL_COMPLETE = 'event_goal_complete';
    case EVENT_NOT_RUNNING = 'event_not_running';
    case EVENT_WRONG_MAP = 'event_wrong_map';
    case EVENT_STEP_CHANGED = 'event_step_changed';
    case EVENT_NO_CRAFTABLE_ITEMS = 'event_no_craftable_items';
    case EVENT_NO_EVENT_ITEMS_TO_ENCHANT = 'event_no_event_items_to_enchant';
    case EVENT_NO_AFFIXES = 'event_no_affixes';
    case NO_CURRENCY = 'no_currency';
    case CRAFT_SET_FULL = 'craft_set_full';
    case CRAFT_SET_COMPLETE = 'craft_set_complete';
    case ENCHANT_SET_COMPLETE = 'enchant_set_complete';
    case CRAFT_ENCHANT_SET_FULL = 'craft_enchant_set_full';
    case CRAFT_ENCHANT_SET_COMPLETE = 'craft_enchant_set_complete';
    case CRAFT_ENCHANT_SET_TARGET_SET_CHANGED = 'craft_enchant_set_target_set_changed';
    case INT_TOO_LOW_FOR_ENCHANTING = 'int_too_low_for_enchanting';
    case ALCHEMY_BAG_FULL = 'alchemy_bag_full';
    case TRINKETRY_INSUFFICIENT_CURRENCIES = 'trinketry_insufficient_currencies';
}
