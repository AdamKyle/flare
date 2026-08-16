<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum BatchCraftingDisposition: string
{
    case KEEP = 'keep';
    case SELL = 'sell';
    case DESTROY = 'destroy';
}
