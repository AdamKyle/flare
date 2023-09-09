<?php

namespace App\Flare\AlchemyItemGenerator\Values;

use Exception;

class AlchemyItemType {

    const INCREASE_STATS = 'increase-stats';
    const INCREASE_DAMAGE = 'increase-damage';
    const INCREASE_ARMOUR = 'increase-armour';
    const INCREASE_HEALING = 'increase-healing';
    const INCREASE_SKILL_TYPE = 'increase-skill-type';

    /**
     * @var array $types
     */
    protected static array $types = [
        self::INCREASE_STATS      => self::INCREASE_STATS,
        self::INCREASE_DAMAGE     => self::INCREASE_DAMAGE,
        self::INCREASE_ARMOUR     => self::INCREASE_ARMOUR,
        self::INCREASE_HEALING    => self::INCREASE_HEALING,
        self::INCREASE_SKILL_TYPE => self::INCREASE_SKILL_TYPE,
    ];

    /**
     * @var string $value
     */
    private string $value;

    /**
     * @param int $value
     * @throws Exception
     */
    public function __construct(string $value) {
        if (!in_array($value, self::$types)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Are we increasing stats?
     *
     * @return boolean
     */
    public function increasesStats(): bool {
        return $this->value === self::INCREASE_STATS;
    }

    /**
     * Are we increasing damage?
     *
     * @return boolean
     */
    public function increasesDamage(): bool {
        return $this->value === self::INCREASE_DAMAGE;
    }

    /**
     * Are we increasing armour?
     *
     * @return boolean
     */
    public function increasesArmour(): bool {
        return $this->value === self::INCREASE_ARMOUR;
    }

    /**
     * Are we increasing healing?
     *
     * @return boolean
     */
    public function increasesHealing(): bool {
        return $this->value === self::INCREASE_HEALING;
    }

    /**
     * Are we increasing a particular skill type?
     *
     * @return boolean
     */
    public function increasesSkillType(): bool {
        return $this->value === self::INCREASE_SKILL_TYPE;
    }
}
