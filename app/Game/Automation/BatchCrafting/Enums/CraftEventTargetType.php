<?php

namespace App\Game\Automation\BatchCrafting\Enums;

use App\Game\Skills\Values\CraftingSkillGroup;

enum CraftEventTargetType
{
    case WEAPON;
    case ARMOUR;
    case RING;
    case DAMAGE_SPELL;
    case HEALING_SPELL;

    /**
     * Return the full ordered five-target Craft For Event rotation.
     *
     * @return array<int, self> The ordered target cases.
     */
    public static function orderedCases(): array
    {
        return self::cases();
    }

    /**
     * Return the Crafting skill group used to resolve the character's skill for this target.
     *
     * @return CraftingSkillGroup The Crafting skill group.
     */
    public function skillGroup(): CraftingSkillGroup
    {
        return match ($this) {
            self::WEAPON => CraftingSkillGroup::WEAPON,
            self::ARMOUR => CraftingSkillGroup::ARMOUR,
            self::RING => CraftingSkillGroup::RING,
            self::DAMAGE_SPELL, self::HEALING_SPELL => CraftingSkillGroup::SPELL,
        };
    }

    /**
     * Return the crafting type used to query craftable items for this target, when fixed.
     *
     * The weapon target has no single fixed crafting type; it spans every weapon subtype.
     *
     * @return string|null The fixed crafting type, or null for the weapon target.
     */
    public function craftingType(): ?string
    {
        return match ($this) {
            self::WEAPON => null,
            self::ARMOUR => 'armour',
            self::RING => 'ring',
            self::DAMAGE_SPELL, self::HEALING_SPELL => 'spell',
        };
    }

    /**
     * Return the specific item type required to narrow this target's craftable items, when fixed.
     *
     * @return string|null The required item type, or null when the target is not narrowed by item type.
     */
    public function itemType(): ?string
    {
        return match ($this) {
            self::WEAPON, self::ARMOUR, self::RING => null,
            self::DAMAGE_SPELL => 'spell-damage',
            self::HEALING_SPELL => 'spell-healing',
        };
    }
}
