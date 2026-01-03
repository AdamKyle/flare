<?php

namespace App\Flare\Items\Values;

enum ItemType: string
{
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
    case TRINKET = 'trinket';
    case ARTIFACT = 'artifact';

    /**
     * Gets a list of all valid weapon types.
     *
     * - Ignores spells
     * - Ignore rings
     */
    public static function validWeapons(): array
    {
        $filtered = array_filter(
            self::cases(),
            fn (self $type) => ! in_array($type, [
                self::SPELL_DAMAGE,
                self::SPELL_HEALING,
                self::RING,
                self::TRINKET,
                self::ARTIFACT,
            ], true)
        );

        // Map to string values and reindex to 0..N for stable comparisons in tests.
        return array_values(array_map(
            fn (self $type) => $type->value,
            $filtered
        ));
    }

    /**
     * Gets all weapon types including spells.
     *
     * - ignores rings
     */
    public static function allWeaponTypes(): array
    {
        return array_map(
            fn (self $type) => $type->value,
            array_filter(self::cases(), fn (self $type) => ! in_array($type, [
                self::RING,
            ], true))
        );
    }

    /**
     * Gets all the weapon mastery types
     *
     * - does not include: Rings, Trinket or Artifacts
     */
    public static function weaponMasteryTypes(): array
    {
        return array_map(
            fn (self $type) => $type->value,
            array_filter(self::cases(), fn (self $type) => ! in_array($type, [
                self::RING,
                self::TRINKET,
                self::ARTIFACT,
            ], true))
        );
    }

    /**
     * Get all types as a string array
     */
    public static function allTypes(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }

    /**
     * Returns the proper name of a weapon type.
     */
    public static function getProperNameForType(string $type): string
    {
        return ucwords(str_replace('-', ' ', $type));
    }

    /**
     * Get valid weapons as options.
     */
    public static function getValidWeaponsAsOptions(): array
    {
        $valid = self::validWeapons();

        return array_combine(
            $valid,
            array_map(fn ($type) => ucwords(str_replace('-', ' ', $type)), $valid)
        );
    }
}
