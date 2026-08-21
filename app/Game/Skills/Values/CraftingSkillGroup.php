<?php

namespace App\Game\Skills\Values;

enum CraftingSkillGroup: string
{
    case WEAPON = 'weapon';
    case ARMOUR = 'armour';
    case RING = 'ring';
    case SPELL = 'spell';

    /**
     * Return the real GameSkill name for this Crafting skill group.
     *
     * @return string The GameSkill name.
     */
    public function skillName(): string
    {
        return match ($this) {
            self::WEAPON => 'Weapon Crafting',
            self::ARMOUR => 'Armour Crafting',
            self::RING => 'Ring Crafting',
            self::SPELL => 'Spell Crafting',
        };
    }
}
