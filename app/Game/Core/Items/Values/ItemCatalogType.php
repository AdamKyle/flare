<?php

namespace App\Game\Core\Items\Values;

enum ItemCatalogType: string
{
    case WEAPON = 'weapon';
    case STAVE = 'stave';
    case BOW = 'bow';
    case DAGGER = 'dagger';
    case SCRATCH_AWL = 'scratch-awl';
    case MACE = 'mace';
    case HAMMER = 'hammer';
    case GUN = 'gun';
    case FAN = 'fan';
    case WAND = 'wand';
    case CENSER = 'censer';
    case CENSOR = 'censor';
    case CLAW = 'claw';
    case SWORD = 'sword';
    case SPELL_HEALING = 'spell-healing';
    case SPELL_DAMAGE = 'spell-damage';
    case RING = 'ring';
    case TRINKET = 'trinket';
    case ARTIFACT = 'artifact';
    case SHIELD = 'shield';
    case BODY = 'body';
    case LEGGINGS = 'leggings';
    case SLEEVES = 'sleeves';
    case GLOVES = 'gloves';
    case FEET = 'feet';
    case HELMET = 'helmet';
    case QUEST = 'quest';
    case ALCHEMY = 'alchemy';
}
