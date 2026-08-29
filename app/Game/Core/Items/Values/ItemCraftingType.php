<?php

namespace App\Game\Core\Items\Values;

enum ItemCraftingType: string
{
    case WEAPON = 'weapon';
    case ARMOUR = 'armour';
    case RING = 'ring';
    case SPELL = 'spell';
    case ARTIFACT = 'artifact';
    case ALCHEMY = 'alchemy';
}
