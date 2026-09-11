<?php

namespace App\Game\Gems\Progression\Values;

enum GemItemRarity: string
{
    case UNIQUE = 'unique';
    case MYTHIC = 'mythic';
    case COSMIC = 'cosmic';
}
