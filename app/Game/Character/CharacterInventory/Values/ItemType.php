<?php

namespace App\Game\Character\CharacterInventory\Values;

enum ItemType: string {
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
    case CLAW = 'claw';
    case SWORD = 'sword';
    case SPELL_HEALING = 'spell-healing';
    case SPELL_DAMAGE = 'spell-damage';
}
