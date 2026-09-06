<?php

namespace App\Game\Gems\Values;

/**
 * The closed set of Monster combat effects a resolved Area Gem context can
 * contribute. Atonement is resolved separately through
 * `ResolvedAreaGemAtonement` and is not part of this set.
 */
enum AreaGemMonsterEffect: string
{
    case ENEMY_STRENGTH_INCREASE = 'enemy_strength_increase';
    case ENEMY_HEALING_INCREASE = 'enemy_healing_increase';
    case ENEMY_SPELL_EVASION = 'enemy_spell_evasion';
    case ENEMY_AFFIX_RESISTANCE = 'enemy_affix_resistance';
    case ENEMY_ENTRANCING_CHANCE = 'enemy_entrancing_chance';
    case ENEMY_DEVOURING_LIGHT_CHANCE = 'enemy_devouring_light_chance';
    case ENEMY_DEVOURING_DARKNESS_CHANCE = 'enemy_devouring_darkness_chance';
    case ENEMY_AMBUSH_CHANCE = 'enemy_ambush_chance';
    case ENEMY_AMBUSH_RESISTANCE = 'enemy_ambush_resistance';
    case ENEMY_COUNTER_CHANCE = 'enemy_counter_chance';
    case ENEMY_COUNTER_RESISTANCE = 'enemy_counter_resistance';
}
