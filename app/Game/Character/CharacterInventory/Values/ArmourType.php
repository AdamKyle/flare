<?php

namespace App\Game\Character\CharacterInventory\Values;

enum ArmourType: string {
    case SHIELD = 'shield';
    case BODY = 'body';
    case LEGGINGS = 'leggings';
    case SLEEVES = 'sleeves';
    case GLOVES = 'gloves';
    case FEET = 'feet';
    case HELMET = 'helmet';
}
