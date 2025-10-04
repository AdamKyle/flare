<?php

namespace App\Game\Character\CharacterInventory\Mappings;

use App\Flare\Items\Values\ItemType;

final class ItemTypeMapping
{
    /**
     * Maps a character class name to an ItemType enum value, an array of ItemType enum values, or null.
     *
     * @return string|string[]|null
     */
    public static function getForClass(string $className): string|array|null
    {
        $normalized = strtolower(trim($className));

        return match ($normalized) {
            'fighter' => ItemType::SWORD->value,
            'vampire' => ItemType::CLAW->value,
            'ranger' => ItemType::BOW->value,
            'prophet' => [ItemType::CENSER->value, ItemType::SPELL_HEALING->value],
            'heretic' => [ItemType::WAND->value, ItemType::STAVE->value, ItemType::SPELL_DAMAGE->value],
            'thief' => [ItemType::DAGGER->value, ItemType::BOW->value],
            'blacksmith' => ItemType::HAMMER->value,
            'arcane alchemist' => [ItemType::STAVE->value, ItemType::SPELL_DAMAGE->value],
            'prisoner' => array_map(fn ($case) => $case->value, ItemType::cases()),
            'merchant' => [ItemType::BOW->value, ItemType::STAVE->value],
            'dancer' => ItemType::FAN->value,
            'cleric' => ItemType::MACE->value,
            'gunslinger' => ItemType::GUN->value,
            'book binder' => ItemType::SCRATCH_AWL->value,
            default => null,
        };
    }
}
