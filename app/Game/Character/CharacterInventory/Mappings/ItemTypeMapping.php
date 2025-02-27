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
            'prophet'          => [ItemType::CENSER, ItemType::SPELL_HEALING],
            'heretic'          => [ItemType::WAND, ItemType::STAVE, ItemType::SPELL_DAMAGE],
            'thief'            => ItemType::DAGGER,
            'blacksmith'       => ItemType::HAMMER,
            'arcane alchemist' => [ItemType::STAVE, ItemType::SPELL_DAMAGE],
            'prisoner'         => ItemType::cases(),
            'merchant'         => [ItemType::BOW, ItemType::STAVE],
            'dancer'           => ItemType::FAN,
            'cleric'           => ItemType::MACE,
            'gunslinger'       => ItemType::GUN,
            'book binder'      => ItemType::SCRATCH_AWL,
            default            => null,
        };
    }
}
