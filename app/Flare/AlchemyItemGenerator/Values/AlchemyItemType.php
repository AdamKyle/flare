<?php

namespace App\Flare\AlchemyItemGenerator\Values;

use Exception;

class AlchemyItemType
{
    const INCREASE_STATS = 'increase-stats';

    const INCREASE_DAMAGE = 'increase-damage';

    const INCREASE_ARMOUR = 'increase-armour';

    const INCREASE_HEALING = 'increase-healing';

    const INCREASE_SKILL_TYPE = 'increase-skill-type';

    const DAMAGES_KINGDOMS = 'damages-kingdoms';

    const HOLY_OILS = 'holy-oils';

    const INCREASE_ALCHEMY_SKILL = 'increases-alchemy-skill';

    protected static array $types = [
        self::INCREASE_STATS => self::INCREASE_STATS,
        self::INCREASE_DAMAGE => self::INCREASE_DAMAGE,
        self::INCREASE_ARMOUR => self::INCREASE_ARMOUR,
        self::INCREASE_HEALING => self::INCREASE_HEALING,
        self::INCREASE_SKILL_TYPE => self::INCREASE_SKILL_TYPE,
        self::DAMAGES_KINGDOMS => self::DAMAGES_KINGDOMS,
        self::HOLY_OILS => self::HOLY_OILS,
        self::INCREASE_ALCHEMY_SKILL => self::INCREASE_ALCHEMY_SKILL,
    ];

    public static $list = [
        self::INCREASE_STATS,
        self::INCREASE_DAMAGE,
        self::INCREASE_ARMOUR,
        self::INCREASE_HEALING,
        self::INCREASE_SKILL_TYPE,
        self::DAMAGES_KINGDOMS,
        self::HOLY_OILS,
        self::INCREASE_ALCHEMY_SKILL,
    ];

    private string $value;

    /**
     * @param  int  $value
     *
     * @throws Exception
     */
    public function __construct(string $value)
    {
        if (! in_array($value, self::$types)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Are we increasing stats?
     */
    public function increasesStats(): bool
    {
        return $this->value === self::INCREASE_STATS;
    }

    /**
     * Are we increasing damage?
     */
    public function increasesDamage(): bool
    {
        return $this->value === self::INCREASE_DAMAGE;
    }

    /**
     * Are we increasing armour?
     */
    public function increasesArmour(): bool
    {
        return $this->value === self::INCREASE_ARMOUR;
    }

    /**
     * Are we increasing healing?
     */
    public function increasesHealing(): bool
    {
        return $this->value === self::INCREASE_HEALING;
    }

    /**
     * Are we increasing a particular skill type?
     */
    public function increasesSkillType(): bool
    {
        return $this->value === self::INCREASE_SKILL_TYPE;
    }

    /**
     * Are we damaging kingdoms?
     */
    public function damagesKingdoms(): bool
    {
        return $this->value === self::DAMAGES_KINGDOMS;
    }

    /**
     * Is this suppose to be a holy oil item?
     */
    public function isHolyOilType(): bool
    {
        return $this->value === self::HOLY_OILS;
    }
}
