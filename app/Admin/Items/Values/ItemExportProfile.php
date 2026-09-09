<?php

namespace App\Admin\Items\Values;

use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemCatalogType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\ItemType;

enum ItemExportProfile: string
{
    case WEAPONS = 'weapons';
    case ARMOUR = 'armour';
    case RINGS = 'rings';
    case SPELLS = 'spells';
    case QUEST = 'quest';
    case ALCHEMY = 'alchemy';
    case TRINKET = 'trinket';
    case ARTIFACT = 'artifact';
    case SPECIALTY_SHOPS = 'specialty-shops';

    /**
     * Return the exact 1.0 Item `type`/`specialty_type` values that belong to this export family.
     */
    public function familyValues(): array
    {
        return match ($this) {
            self::WEAPONS => [
                ItemType::WEAPON->value,
                ItemType::BOW->value,
                ItemType::HAMMER->value,
                ItemType::STAVE->value,
                ItemType::GUN->value,
                ItemType::FAN->value,
                ItemType::SCRATCH_AWL->value,
                ItemType::MACE->value,
            ],
            self::ARMOUR => ArmourType::allTypes(),
            self::RINGS => [ItemType::RING->value],
            self::SPELLS => [
                ItemType::SPELL_DAMAGE->value,
                ItemType::SPELL_HEALING->value,
            ],
            self::QUEST => [ItemCatalogType::QUEST->value],
            self::ALCHEMY => [ItemCatalogType::ALCHEMY->value],
            self::TRINKET => [ItemType::TRINKET->value],
            self::ARTIFACT => [ItemType::ARTIFACT->value],
            self::SPECIALTY_SHOPS => array_map(
                fn (ItemSpecialtyType $type): string => $type->value,
                ItemSpecialtyType::cases()
            ),
        };
    }
}
