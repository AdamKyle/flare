<?php

namespace App\Game\Character\CharacterInventory\Values;

enum AlchemyItemType: string {
    case INCREASE_STATS = 'increase-stats';
    case INCREASE_DAMAGE = 'increase-damage';
    case INCREASE_ARMOUR = 'increase-armour';
    case INCREASE_HEALING = 'increase-healing';
    case INCREASE_SKILL_TYPE = 'increase-skill-type';
    case DAMAGES_KINGDOMS = 'damages-kingdoms';
    case HOLY_OILS = 'holy-oils';
    case INCREASE_ALCHEMY_SKILL = 'increases-alchemy-skill';

    /**
     * Returns whether this alchemy item type increases stats.
     *
     * @return bool
     */
    public function increasesStats(): bool
    {
        return $this === self::INCREASE_STATS;
    }

    /**
     * Returns whether this alchemy item type increases damage.
     *
     * @return bool
     */
    public function increasesDamage(): bool
    {
        return $this === self::INCREASE_DAMAGE;
    }

    /**
     * Returns whether this alchemy item type increases armour.
     *
     * @return bool
     */
    public function increasesArmour(): bool
    {
        return $this === self::INCREASE_ARMOUR;
    }

    /**
     * Returns whether this alchemy item type increases healing.
     *
     * @return bool
     */
    public function increasesHealing(): bool
    {
        return $this === self::INCREASE_HEALING;
    }

    /**
     * Returns whether this alchemy item type increases a skill type.
     *
     * @return bool
     */
    public function increasesSkillType(): bool
    {
        return $this === self::INCREASE_SKILL_TYPE;
    }

    /**
     * Returns whether this alchemy item type damages kingdoms.
     *
     * @return bool
     */
    public function damagesKingdoms(): bool
    {
        return $this === self::DAMAGES_KINGDOMS;
    }

    /**
     * Returns whether this alchemy item type is a holy oil type.
     *
     * @return bool
     */
    public function isHolyOilType(): bool
    {
        return $this === self::HOLY_OILS;
    }
}
