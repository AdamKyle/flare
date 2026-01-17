<?php

namespace App\Game\Market\Enums;

enum MarketHistorySecondaryFilter: string
{
    case SingleEnchant = 'single_enchant';
    case DoubleEnchant = 'double_enchant';
    case Unique = 'unique';
    case Mythic = 'mythic';
    case Cosmic = 'cosmic';
}
