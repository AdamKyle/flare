<?php

namespace App\Flare\View\Livewire\Admin\Items\Values;

enum ItemTableSelectOptions: string
{
    case NONE = '';
    case WEAPON = 'weapon';
    case BOW = 'bow';
    case GUN = 'gun';
    case FAN = 'fan';
    case MACE = 'mace';
    case SCRATCH_AWL = 'scratch-awl';
    case STAVE = 'stave';
    case DAGGER = 'dagger';
    case CENSER = 'censer';
    case SWORDS = 'swords';
    case CLAW = 'claw';
    case WAND = 'wand';
    case HAMMER = 'hammer';
    case BODY = 'body';
    case HELMET = 'helmet';
    case SHIELD = 'shield';
    case SLEEVES = 'sleeves';
    case GLOVES = 'gloves';
    case LEGGINGS = 'leggings';
    case FEET = 'feet';
    case RING = 'ring';
    case SPELL_HEALING = 'spell-healing';
    case SPELL_DAMAGE = 'spell-damage';
    case TRINKET = 'trinket';
    case QUEST = 'quest';
    case ALCHEMY = 'alchemy';
    case ARTIFACT = 'artifact';

    public static function getLabels(): array
    {
        return [
            self::NONE->value => 'Please Select',
            self::WEAPON->value => 'Weapons',
            self::BOW->value => 'Bows',
            self::GUN->value => 'Guns',
            self::FAN->value => 'Fans',
            self::MACE->value => 'Maces',
            self::SCRATCH_AWL->value => 'Scratch Awl',
            self::STAVE->value => 'Staves',
            self::DAGGER->value => 'Daggers',
            self::CENSER->value => 'Censers',
            self::SWORDS->value => 'Swords',
            self::CLAW->value => 'Claws',
            self::WAND->value => 'Wands',
            self::HAMMER->value => 'Hammers',
            self::BODY->value => 'Body',
            self::HELMET->value => 'Helmets',
            self::SHIELD->value => 'Shields',
            self::SLEEVES->value => 'Sleeves',
            self::GLOVES->value => 'Gloves',
            self::LEGGINGS->value => 'Leggings',
            self::FEET->value => 'Feet',
            self::RING->value => 'Rings',
            self::SPELL_HEALING->value => 'Healing Spells',
            self::SPELL_DAMAGE->value => 'Damage Spells',
        ];
    }
}
