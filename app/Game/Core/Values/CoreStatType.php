<?php

namespace App\Game\Core\Values;

enum CoreStatType: string
{
    case STRENGTH = 'str';
    case DURABILITY = 'dur';
    case DEXTERITY = 'dex';
    case CHARISMA = 'chr';
    case INTELLIGENCE = 'int';
    case AGILITY = 'agi';
    case FOCUS = 'focus';
}
