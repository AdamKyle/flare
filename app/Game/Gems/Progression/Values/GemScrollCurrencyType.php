<?php

namespace App\Game\Gems\Progression\Values;

/**
 * The closed set of Character currencies a generated Currency Gem Scroll can target.
 */
enum GemScrollCurrencyType: string
{
    case GOLD = 'gold';
    case COPPER_COINS = 'copper_coins';
    case GOLD_DUST = 'gold_dust';
    case SHARDS = 'shards';
}
