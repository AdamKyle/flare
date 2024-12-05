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

    public function getValue(): string
    {
        return $this->value;
    }
}
