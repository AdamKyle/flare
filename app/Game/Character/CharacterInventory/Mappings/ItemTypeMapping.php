<?php

namespace App\Game\Character\CharacterInventory\Mappings;

use App\Game\Character\CharacterInventory\Values\ItemType;

final class ItemTypeMapping
{
    /**
     * Maps a character class name to an ItemType enum value, an array of ItemType enum values, or null.
     *
     * @param string $className
     * @return ItemType|ItemType[]|null
     */
    public static function getForClass(string $className): ItemType|array|null
    {
        $normalized = strtolower(trim($className));

        return match ($normalized) {
            'fighter'          => ItemType::SWORD,
            'vampire'          => ItemType::CLAW,
            'ranger'           => ItemType::BOW,
            'prophet'          => ItemType::CENSER,
            'heretic'          => ItemType::WAND,
            'thief'            => ItemType::DAGGER,
            'blacksmith'       => ItemType::HAMMER,
            'arcane alchemist' => ItemType::STAVE,
            'prisoner'         => self::allWeapons(),
            'merchant'         => self::merchantWeapons(),
            'dancer'           => ItemType::FAN,
            'cleric'           => ItemType::MACE,
            'gunslinger'       => ItemType::GUN,
            'book binder'      => ItemType::SCRATCH_AWL,
            default            => null,
        };
    }

    /**
     * Returns an array of all available weapon types.
     *
     * @return ItemType[]
     */
    private static function allWeapons(): array
    {
        return ItemType::cases();
    }

    /**
     * Returns an array containing ItemType::BOW and ItemType::STAVE.
     *
     * @return ItemType[]
     */
    private static function merchantWeapons(): array
    {
        return [ItemType::BOW, ItemType::STAVE];
    }
}
