<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum CraftingMessageTypes: string implements BaseMessageType
{
    case CRAFTED = 'crafted';
    case DISENCHANTED = 'disenchanted';
    case CRAFTED_GEM = 'crafted_gem';
    case ENCHANTMENT_FAILED = 'enchantment_failed';
    case ENCHANTED = 'enchanted';
    case TRANSMUTED = 'transmuted';
    case TO_HARD_TO_CRAFT = 'to_hard_to_craft';
    case TO_EASY_TO_CRAFT = 'to_easy_to_craft';
    case INT_TO_LOW_ENCHANTING = 'int_to_low_enchanting';
    case FAILED_TO_CRAFT = 'failed_to_craft';
    case FAILED_TO_DISENCHANT = 'failed_to_disenchant';
    case FAILED_TO_TRANSMUTE = 'failed_to_transmute';

    public function getValue(): string
    {
        return $this->value;
    }
}
