<?php

namespace App\Game\Gems\Values;

enum GemAbilityScalingSource: string
{
    case WEAPON_ATTACK = 'weapon_attack';
    case SPELL_ATTACK = 'spell_attack';
    case DAMAGE_STAT = 'damage_stat';
    case DEFENCE = 'defence';
}
