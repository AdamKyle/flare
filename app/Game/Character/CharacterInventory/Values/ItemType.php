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
    case RING = 'ring';

    public static function validWeapons(): array {
        return array_map(
            fn(self $type) => $type->value,
            array_filter(self::cases(), fn(self $type) => !in_array($type, [
                self::SPELL_DAMAGE,
                self::SPELL_HEALING,
                self::RING,
            ]))
        );
    }

    public static function allWeaponTypes(): array {
        return array_map(
            fn(self $type) => $type->value,
            array_filter(self::cases(), fn(self $type) => !in_array($type, [
                self::RING,
            ]))
        );
    }

    public static function getProperNameForType(string $type): string {
        return ucwords(str_replace('-', ' ', $type));
    }
}
