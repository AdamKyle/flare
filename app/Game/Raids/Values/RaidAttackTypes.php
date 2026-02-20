<?php

namespace App\Game\Raids\Values;

use Exception;

class RaidAttackTypes
{
    const FIRE_ATTACK = 0;

    const ICE_ATTACK = 1;

    const WATER_ATTACK = 2;

    const ENRAGED_HATE = 3;

    private int $value;

    /**
     * @var int[]
     */
    protected static array $values = [
        0 => self::FIRE_ATTACK,
        1 => self::ICE_ATTACK,
        2 => self::WATER_ATTACK,
        3 => self::ENRAGED_HATE,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Are we a fire attack type?
     */
    public function isFireAttack(): bool
    {
        return $this->value === self::FIRE_ATTACK;
    }

    /**
     * Are we ice attack type?
     */
    public function isIceAttack(): bool
    {
        return $this->value === self::ICE_ATTACK;
    }

    /**
     * Are we water based attack?
     */
    public function isWaterAttack(): bool
    {
        return $this->value === self::WATER_ATTACK;
    }

    public function isEnragedHate(): bool {
        return $this->value === self::ENRAGED_HATE;
    }
}
