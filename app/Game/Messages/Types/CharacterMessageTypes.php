<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum CharacterMessageTypes: string implements BaseMessageType
{
    case LEVEL_UP = 'level_up';
    case SILENCED = 'silenced';
    case SOLD_ITEM_ON_MARKET = 'sold_item_on_market';

    public function getValue(): string
    {
        return $this->value;
    }
}
