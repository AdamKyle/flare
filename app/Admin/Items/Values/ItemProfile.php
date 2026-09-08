<?php

namespace App\Admin\Items\Values;

use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemCatalogType;
use App\Game\Core\Items\Values\ItemType;

enum ItemProfile: string
{
    case ALL = 'all';
    case WEAPONS = 'weapons';
    case ARMOUR = 'armour';
    case DAMAGE_SPELLS = 'damage-spells';
    case HEALING_SPELLS = 'healing-spells';
    case RINGS = 'rings';
    case TRINKETS = 'trinkets';
    case ARTIFACTS = 'artifacts';
    case QUEST_ITEMS = 'quest-items';
    case ALCHEMY = 'alchemy';
    case SPECIALTY = 'specialty';

    /**
     * Return the current 2.0 Item `type` values that belong to this profile.
     */
    public function types(): ?array
    {
        return match ($this) {
            self::ALL, self::SPECIALTY => null,
            self::WEAPONS => ItemType::validWeapons(),
            self::ARMOUR => ArmourType::allTypes(),
            self::DAMAGE_SPELLS => [ItemType::SPELL_DAMAGE->value],
            self::HEALING_SPELLS => [ItemType::SPELL_HEALING->value],
            self::RINGS => [ItemType::RING->value],
            self::TRINKETS => [ItemType::TRINKET->value],
            self::ARTIFACTS => [ItemType::ARTIFACT->value],
            self::QUEST_ITEMS => [ItemCatalogType::QUEST->value],
            self::ALCHEMY => [ItemCatalogType::ALCHEMY->value],
        };
    }

    /**
     * Return the valid subtype values for profiles that support subtype filtering.
     */
    public function subtypes(): ?array
    {
        return match ($this) {
            self::WEAPONS => ItemType::validWeapons(),
            self::ARMOUR => ArmourType::allTypes(),
            default => null,
        };
    }

    /**
     * Whether this profile filters the catalog by having a non-null `specialty_type`.
     */
    public function requiresSpecialtyType(): bool
    {
        return $this === self::SPECIALTY;
    }

    /**
     * Return the Item list column sort keys allowed for this profile.
     */
    public function allowedSortKeys(): array
    {
        return match ($this) {
            self::ALL => ['name', 'type', 'can_craft', 'usable', 'market_sellable'],
            self::WEAPONS => ['name', 'type', 'base_damage', 'cost', 'skill_level_required'],
            self::ARMOUR => ['name', 'type', 'base_ac', 'cost', 'skill_level_required'],
            self::DAMAGE_SPELLS => ['name', 'base_damage', 'cost', 'skill_level_required', 'skill_level_trivial'],
            self::HEALING_SPELLS => ['name', 'base_healing', 'cost', 'skill_level_required', 'skill_level_trivial'],
            self::RINGS => ['name', 'base_damage_mod', 'base_ac_mod', 'base_healing_mod', 'cost'],
            self::TRINKETS => ['name', 'ambush_chance', 'ambush_resistance', 'counter_chance', 'counter_resistance'],
            self::ARTIFACTS => ['name', 'item_skill_id', 'specialty_type', 'cost', 'can_craft'],
            self::QUEST_ITEMS => ['name', 'drop_location_id', 'effect', 'unlocks_class_id', 'can_drop'],
            self::ALCHEMY => ['name', 'alchemy_type', 'gold_dust_cost', 'shards_cost', 'skill_level_required'],
            self::SPECIALTY => ['name', 'specialty_type', 'type', 'cost', 'gold_bars_cost'],
        };
    }
}
