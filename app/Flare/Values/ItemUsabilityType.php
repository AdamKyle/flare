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

    /**
     * @var string[] $values
     */
    protected static $values = [
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

    public function isStatIncrease(): bool {
        return $this->value === self::STAT_INCREASE;
    }

    public function effectsSkill(): bool {
        return $this->value === self::EFFECTS_SKILL;
    }

    public function damagesKingdom(): bool {
        return $this->value === self::KINGDOM_DAMAGE;
    }
}
