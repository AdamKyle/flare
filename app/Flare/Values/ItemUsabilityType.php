<?php

namespace App\Flare\Values;

class ItemUsabilityType {

    /**
     * @var string $value
     */
    private $value;

    const STAT_INCREASE  = 0;
    const EFFECTS_SKILL  = 1;
    const KINGDOM_DAMAGE = 2;

    protected static $values = [
        0 => self::STAT_INCREASE,
        1 => self::EFFECTS_SKILL,
        2 => self::KINGDOM_DAMAGE,
    ];

    /**
     * @var string[] $values
     */
    protected static $namedValues = [
        self::STAT_INCREASE  => 'Stat increase',
        self::EFFECTS_SKILL  => 'Effects skill',
        self::KINGDOM_DAMAGE => 'Deals damage to a kingdom',
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws \Exception
     */
    public function __construct(int $value)
    {

        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Does the use of this item increase stats?
     *
     * @return bool
     */
    public function isStatIncrease(): bool {
        return $this->value === self::STAT_INCREASE;
    }

    /**
     * Does the use of this item effect skills?
     *
     * @return bool
     */
    public function effectsSkill(): bool {
        return $this->value === self::EFFECTS_SKILL;
    }

    /**
     * Does the use of this item damage kingdoms?
     *
     * @return bool
     */
    public function damagesKingdom(): bool {
        return $this->value === self::KINGDOM_DAMAGE;
    }

    /**
     * Get the named value.
     *
     * @return string
     */
    public function getNamedValue(): string {
        return self::$namedValues[$this->value];
    }
}
