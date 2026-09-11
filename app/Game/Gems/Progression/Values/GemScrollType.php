<?php

namespace App\Game\Gems\Progression\Values;

/**
 * The closed set of Gem Scroll families a generated Gem Scroll Item can belong to.
 */
enum GemScrollType: string
{
    case XP = 'xp';
    case CURRENCY = 'currency';
    case ITEM = 'item';
}
