<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum BatchCraftingOutputDestination: string
{
    case INVENTORY = 'inventory';
    case CRAFTED_ITEMS_SET = 'crafted_items_set';
    case INVENTORY_SET = 'inventory_set';
}
