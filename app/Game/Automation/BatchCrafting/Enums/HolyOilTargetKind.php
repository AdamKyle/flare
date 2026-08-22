<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum HolyOilTargetKind: string
{
    case INVENTORY_SLOT = 'inventory_slot';
    case SET_SLOT = 'set_slot';
}
