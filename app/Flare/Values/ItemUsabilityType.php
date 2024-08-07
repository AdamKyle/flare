<?php

namespace App\Flare\Values;

class ItemUsabilityType
{
    /**
     * @var string
     */
    private $value;

    const STAT_INCREASE = 0;

    const EFFECTS_SKILL = 1;

    const KINGDOM_DAMAGE = 2;

    const OTHER = 3;

    const USE_ON_ITEMS = 4;

    protected static $values = [
        0 => self::STAT_INCREASE,
        1 => self::EFFECTS_SKILL,
        2 => self::KINGDOM_DAMAGE,
        3 => self::OTHER,
        4 => self::USE_ON_ITEMS,
    ];

    /**
     * @var string[]
     */
    protected static $namedValues = [
        self::STAT_INCREASE => 'Stat increase',
        self::EFFECTS_SKILL => 'Effects skill',
        self::KINGDOM_DAMAGE => 'Deals damage to a kingdom',
        self::OTHER => 'Effects multiple stats',
        self::USE_ON_ITEMS => 'Use on items.',
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @throws \Exception
     */
    public function __construct(int $value)
    {

        if (! in_array($value, self::$values)) {
            throw new \Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Does the use of this item increase stats?
     */
    public function isStatIncrease(): bool
    {
        return $this->value === self::STAT_INCREASE;
    }

    /**
     * Does the use of this item effect skills?
     */
    public function effectsSkill(): bool
    {
        return $this->value === self::EFFECTS_SKILL;
    }

    /**
     * Does the use of this item damage kingdoms?
     */
    public function damagesKingdom(): bool
    {
        return $this->value === self::KINGDOM_DAMAGE;
    }

    /**
     * is Other?
     */
    public function isOther(): bool
    {
        return $this->value === self::OTHER;
    }

    /**
     * Can we use this item on other items?
     */
    public function canUseOnItems(): bool
    {
        return $this->value === self::USE_ON_ITEMS;
    }

    /**
     * Get the named value.
     */
    public function getNamedValue(): string
    {
        return self::$namedValues[$this->value];
    }
}
