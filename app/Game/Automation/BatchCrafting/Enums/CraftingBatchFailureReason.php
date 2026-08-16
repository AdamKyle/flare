<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum CraftingBatchFailureReason: string
{
    case NOT_ENOUGH_GOLD = 'not_enough_gold';
    case SKILL_TOO_LOW = 'skill_too_low';
    case FAILED_ROLL = 'failed_roll';
}
