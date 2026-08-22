<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum EnchantEventPhase: string
{
    case ENCHANT_EVENT_INVENTORY = 'enchant_event_inventory';
    case CRAFT_FALLBACK_SET = 'craft_fallback_set';
    case ENCHANT_FALLBACK_SET = 'enchant_fallback_set';
}
