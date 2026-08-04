<?php

namespace App\Game\Core\Currency\Values;

enum CurrencyType: int
{
    case GOLD = 0;
    case GOLD_DUST = 1;
    case SHARDS = 2;
    case COPPER = 3;
}
