<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum CharacterMessageTypes: string implements BaseMessageType
{
    case LEVEL_UP = 'level_up';
    case SILENCED = 'silenced';
    case SOLD_ITEM_ON_MARKET = 'sold_item_on_market';
    case INVENTORY_IS_FULL = 'inventory_is_full';
    case NOT_ENOUGH_GOLD = 'not_enough_gold';
    case NOT_ENOUGH_GOLD_DUST = 'not_enough_gold_dust';
    case NOT_ENOUGH_SHARDS = 'not_enough_shards';
    case NEW_DAMAGE_STAT = 'new_damage_stat';

    public function getValue(): string
    {
        return $this->value;
    }
}
