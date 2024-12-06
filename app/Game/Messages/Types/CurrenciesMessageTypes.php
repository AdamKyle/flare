<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum CurrenciesMessageTypes: string implements BaseMessageType
{
    case GOLD = 'gold';
    case GOLD_RUSH = 'gold_rush';
    case GOLD_CAPPED = 'gold_capped';
    case GOLD_DUST = 'gold_dust';
    case SHARDS = 'shards';
    case COPPER_COINS = 'copper_coins';

    public function getValue(): string
    {
        return $this->value;
    }
}
