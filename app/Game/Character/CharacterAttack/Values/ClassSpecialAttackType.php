<?php

namespace App\Game\Character\CharacterAttack\Values;

enum ClassSpecialAttackType: string
{
    case VAMPIRE_THIRST = 'vampire thirst';
    case PROPHET_HEALING = 'prophet healing';
    case RANGER_TRIPLE_ATTACK = 'ranger triple attack';
    case THIEVES_SHADOW_DANCE = 'thieves shadow dance';
    case HERETICS_DOUBLE_CAST = 'heretics double cast';
    case FIGHTERS_DOUBLE_DAMAGE = 'double damage';
    case BLACKSMITHS_HAMMER_SMASH = 'hammer smash';
    case ARCANE_ALCHEMISTS_DREAMS = 'alchemists ravenous dream';
    case PRISONER_RAGE = 'prisoner rage';
    case ALCOHOLIC_PUKE = 'alcoholic puke';
    case MERCHANTS_SUPPLY = 'merchants supply';
    case GUNSLINGERS_ASSASSINATION = 'gunslingers assassination';
    case SENSUAL_DANCE = 'sensual dance';
    case BOOK_BINDERS_FEAR = 'book binders fear';
    case HOLY_SMITE = 'holy smite';
    case PLAGUE_SURGE = 'plugue surge';
    case BUCCANEERS_BARRAGE = 'buccaneers barrage';
    case BUCCANEERS_DUAL_GUN_BARRAGE = 'buccaneers dual gun barrage';
    case DEVILS_PIERCING_SHOT = 'devils piercing shot';
    case BEAST_STOMP = 'beast stomp';
}
