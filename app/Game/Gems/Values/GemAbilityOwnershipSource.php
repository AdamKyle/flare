<?php

namespace App\Game\Gems\Values;

enum GemAbilityOwnershipSource: string
{
    case INVENTORY = 'inventory';
    case EQUIPPED = 'equipped';
    case SET = 'set';
}
