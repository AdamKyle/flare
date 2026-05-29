<?php

namespace App\Game\Automation\Enums;

enum AutomatedCraftingResultType: string
{
    case CRAFTED_TARGET_ITEM = 'crafted_target_item';
    case CRAFTED_TRAINING_ITEM = 'crafted_training_item';
    case NOT_ENOUGH_GOLD = 'not_enough_gold';
    case ITEM_NOT_FOUND = 'item_not_found';
    case NO_CRAFTING_SKILL = 'no_crafting_skill';
    case NO_TRAINING_ITEM = 'no_training_item';
    case MAX_ATTEMPTS_REACHED = 'max_attempts_reached';
}
