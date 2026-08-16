<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum BatchCraftingStatus: string
{
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case INFO = 'info';
}
