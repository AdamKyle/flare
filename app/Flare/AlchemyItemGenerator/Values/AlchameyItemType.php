<?php

namespace App\Flare\AlchemyItemGenerator\Values;

use Exception;

class AlchemyItemType {

    const INCREASE_STATS = 'increase-stats';
    const INCREASE_DAMAGE = 'increase-damage';
    const INCREASE_ARMOUR = 'increase-armour';
    const INCREASE_HEALING = 'increase-healing';
    const INCREASE_SKILL_TYPE = 'increase-skill-type';
    const INCREASE_XP_BONUS = 'increase-xp-bonus';

    protected array $types = [
        self::INCREASE_STATS      => self::INCREASE_STATS,
        self::INCREASE_DAMAGE     => self::INCREASE_DAMAGE,
        self::INCREASE_ARMOUR     => self::INCREASE_ARMOUR,
        self::INCREASE_HEALING    => self::INCREASE_HEALING,
        self::INCREASE_SKILL_TYPE => self::INCREASE_SKILL_TYPE,
        self::INCREASE_XP_BONUS   => self::INCREASE_XP_BONUS,
    ];

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
}
