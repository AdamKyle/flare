<?php

namespace App\Game\Messages\Values;

enum ServerMessageSource: string
{
    case INVENTORY_ITEM = 'inventory-item';
    case QUEST_ITEM = 'quest-item';
}
